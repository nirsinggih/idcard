<?php
require('fpdf/fpdf.php');

function cropCircle($srcPath, $destPath, $size = 500) {
    $src = imagecreatefromstring(file_get_contents($srcPath));
    $w = imagesx($src);
    $h = imagesy($src);

    // Ambil square dari tengah gambar
    $side = min($w, $h);
    $srcX = ($w - $side) / 2;
    $srcY = ($h - $side) / 2;

    // Potong bagian tengah
    $square = imagecreatetruecolor($side, $side);
    imagecopy($square, $src, 0, 0, $srcX, $srcY, $side, $side);

    // Gambar lingkaran transparan
    $circle = imagecreatetruecolor($size, $size);
    imagesavealpha($circle, true);
    $trans = imagecolorallocatealpha($circle, 0, 0, 0, 127);
    imagefill($circle, 0, 0, $trans);

    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            $dx = $x - $size / 2;
            $dy = $y - $size / 2;
            if ($dx * $dx + $dy * $dy <= ($size / 2) * ($size / 2)) {
                $sx = ($x / $size) * $side;
                $sy = ($y / $size) * $side;
                $color = imagecolorat($square, $sx, $sy);
                imagesetpixel($circle, $x, $y, $color);
            }
        }
    }

    imagepng($circle, $destPath);
    imagedestroy($circle);
    imagedestroy($square);
    imagedestroy($src);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $sekolah = $_POST['sekolah'];

    // Upload foto
    $foto = $_FILES['foto'];
    $fotoPath = 'uploads/' . uniqid() . '_' . basename($foto['name']);
    move_uploaded_file($foto['tmp_name'], $fotoPath);

    // Buat versi lingkaran dari tengah
    $circlePath = 'uploads/circle_' . uniqid() . '.png';
    cropCircle($fotoPath, $circlePath, 500); // 500px lingkaran

    // Buat PDF ukuran 95 x 124 mm (potret)
    $pdf = new FPDF('P', 'mm', [95, 124]);
    $pdf->AddPage();

    // Background
    $pdf->Image('assets/background.jpg', 0, 0, 95, 124);

    // Foto lingkaran (diameter 50 mm)
    $pdf->Image($circlePath, 27, 35, 40, 40);

    // Nama siswa
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(10, 90);
    $pdf->MultiCell(75, 8, $nama, 0, 'C');

    // Asal sekolah
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 102, 204); // Warna biru (RGB)
    $pdf->SetXY(10, 97);
    $pdf->MultiCell(75, 6, $sekolah, 0, 'C');

    // Output PDF
    $pdf->Output('I', 'id_card_'.$nama.'.pdf');

    // Hapus file sementara
    unlink($fotoPath);
    unlink($circlePath);
}
?>
