<?php
// Xử lý upload khi có POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipa = $_FILES['ipa'];
    $appName = $_POST['app_name'];
    $bundleId = $_POST['bundle_id'];
    $version = $_POST['version'];

    // Tạo slug từ tên app
    $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $appName) . "_" . time();
    $dir = "uploads/$slug";

    // Tạo thư mục chứa file
    if (!mkdir($dir, 0777, true)) {
        die("❌ Không thể tạo thư mục lưu trữ IPA.");
    }

    $ipaPath = "$dir/" . basename($ipa['name']);

    // ✅ Kiểm tra lỗi upload
    if ($ipa['error'] !== UPLOAD_ERR_OK) {
        die("❌ Lỗi khi upload IPA. Mã lỗi: " . $ipa['error']);
    }

    // ✅ Di chuyển file tạm vào thư mục đích
    if (!move_uploaded_file($ipa['tmp_name'], $ipaPath)) {
        die("❌ Không thể lưu file IPA vào thư mục $ipaPath");
    }

    // ✅ Đọc template và tạo manifest
    $template = file_get_contents('manifest-template.plist');
    if (!$template) {
        die("❌ Không thể đọc file template manifest.");
    }

    $plist = str_replace(
        ['{{IPA_URL}}', '{{BUNDLE_ID}}', '{{VERSION}}', '{{TITLE}}'],
        [
            "https://yourdomain.com/$ipaPath",
            $bundleId,
            $version,
            $appName
        ],
        $template
    );

    file_put_contents("$dir/manifest.plist", $plist);

    $link = "itms-services://?action=download-manifest&url=https://yourdomain.com/$dir/manifest.plist";

    $uploadSuccess = true;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Upload IPA</title>
</head>
<body>
    <h1>📲 Upload IPA</h1>
    <form method="POST" enctype="multipart/form-data">
        <label>Tên app: <input type="text" name="app_name" lang="vi" required></label><br><br>
        <label>Bundle ID: <input type="text" name="bundle_id" required></label><br><br>
        <label>Version: <input type="text" name="version" required></label><br><br>
        <label>Chọn file IPA: <input type="file" name="ipa" accept=".ipa" required></label><br><br>
        <button type="submit">Upload</button>
    </form>

    <?php if (!empty($uploadSuccess)): ?>
        <p style="color:green;">✅ Tải lên thành công!</p>
        <a class="install-link" href="<?= htmlspecialchars($link) ?>">Cài đặt <?= htmlspecialchars($appName) ?></a><br><br>
        <input type="text" id="installLink" value="<?= htmlspecialchars($link) ?>" readonly style="width: 80%; padding: 5px;" />
        <button onclick="copyLink()">Copy Link</button>

        <script>
        function copyLink() {
            var copyText = document.getElementById("installLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("✅ Link đã được copy: " + copyText.value);
        }
        </script>
    <?php endif; ?>
</body>
</html>
