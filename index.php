<?php
// Đọc danh sách app để hiển thị
$uploadsDir = 'uploads';
$apps = [];
if (is_dir($uploadsDir)) {
    $folders = array_filter(glob($uploadsDir . '/*'), 'is_dir');
    foreach ($folders as $folder) {
        $slug = basename($folder);
        $manifestPath = "$folder/manifest.plist";
        if (file_exists($manifestPath)) {
            $apps[] = [
                'slug' => $slug,
                'manifestUrl' => "https://yourdomain.com/$folder/manifest.plist",
                'appName' => explode('_', $slug)[0],
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>AppCenter Tự Chế</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />
  <style>
    * {
      font-family: 'Inter', sans-serif;
      box-sizing: border-box;
    }
    body {
      background: #f5f5f5;
      margin: 0;
      padding: 30px;
      display: flex;
      gap: 20px;
      min-height: 100vh;
    }
    nav {
      width: 200px;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      flex-shrink: 0;
    }
    nav h2 {
      margin-top: 0;
      font-size: 1.2rem;
      color: #007aff;
      margin-bottom: 15px;
    }
    nav ul {
      list-style: none;
      padding-left: 0;
    }
    nav ul li {
      margin-bottom: 10px;
    }
    nav ul li a {
      color: #333;
      text-decoration: none;
    }
    nav ul li a:hover {
      text-decoration: underline;
    }
    main {
      flex-grow: 1;
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      max-width: 600px;
    }
    h2 {
      margin-bottom: 20px;
      color: #333;
    }
    input[type="file"],
    input[type="text"],
    input[type="submit"] {
      width: 100%;
      padding: 12px;
      margin: 8px 0 16px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    input[type="submit"] {
      background: #007aff;
      color: white;
      border: none;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background: #005fcc;
    }
    #progressWrapper {
      width: 100%;
      background: #eee;
      height: 20px;
      border-radius: 10px;
      overflow: hidden;
    }
    #progressBar {
      width: 0%;
      height: 100%;
      background: #28a745;
      transition: width 0.2s;
    }
    #status {
      margin-top: 15px;
      color: #333;
    }
    a.install-link {
      display: inline-block;
      margin-top: 10px;
      padding: 10px 14px;
      background: #007aff;
      color: white;
      border-radius: 6px;
      text-decoration: none;
    }
    a.install-link:hover {
      background: #005fcc;
    }
  </style>
</head>
<body>
  <nav>
    <h2>Menu</h2>
    <ul>
      <li><a href="#upload">Upload Ứng Dụng</a></li>
      <li><a href="#apps">Danh sách Apps</a></li>
    </ul>
  </nav>

  <main>
    <section id="upload">
      <h2>Upload ứng dụng iOS (.ipa)</h2>
      <form id="uploadForm" method="POST" enctype="multipart/form-data">
        <input type="file" name="ipa" accept=".ipa" required />
        <input type="text" name="app_name" placeholder="Tên app" required />
        <input type="text" name="bundle_id" placeholder="Bundle ID (vd: com.your.app)" required />
        <input type="text" name="version" placeholder="Phiên bản (vd: 1.0.0)" required />
        <div id="progressWrapper"><div id="progressBar"></div></div>
        <input type="submit" value="Tải lên & Tạo link cài đặt" />
      </form>
      <div id="status"></div>
    </section>

    <hr />

    <section id="apps">
      <h2>📱 Danh sách các app đã upload</h2>
      <ul>
        <?php if (empty($apps)): ?>
          <li>Chưa có app nào được upload.</li>
        <?php else: ?>
          <?php foreach ($apps as $app): ?>
            <li>
              <strong><?= htmlspecialchars($app['appName']) ?></strong> - 
              <a 
                class="install-link" 
                href="itms-services://?action=download-manifest&url=<?= urlencode($app['manifestUrl']) ?>"
                target="_blank"
                >Cài đặt</a>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </section>
  </main>

  <script>
    const form = document.getElementById('uploadForm');
    const progressBar = document.getElementById('progressBar');
    const statusDiv = document.getElementById('status');

    form.onsubmit = function(e) {
      e.preventDefault();
      statusDiv.textContent = '';
      progressBar.style.width = '0%';

      const formData = new FormData(form);
      const xhr = new XMLHttpRequest();

      xhr.open('POST', 'upload.php', true);

      xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
          const percent = (e.loaded / e.total) * 100;
          progressBar.style.width = percent + '%';
        }
      };

      xhr.onload = function() {
        if (xhr.status === 200) {
          statusDiv.innerHTML = xhr.responseText;
          progressBar.style.width = '100%';
          // Có thể reload trang sau khi upload thành công để cập nhật danh sách app
          // setTimeout(() => location.reload(), 2000);
        } else {
          statusDiv.innerHTML = '<b>❌ Lỗi khi upload</b>';
        }
      };

      xhr.onerror = function() {
        statusDiv.innerHTML = '<b>❌ Lỗi khi upload (network error)</b>';
      };

      xhr.send(formData);
    };

    // Copy link function nếu có nút copy trong response
    function copyLink() {
      const copyText = document.getElementById('installLink');
      if (!copyText) return;
      copyText.select();
      copyText.setSelectionRange(0, 99999);
      document.execCommand('copy');
      alert('Link đã được copy: ' + copyText.value);
    }
  </script>
</body>
</html>
