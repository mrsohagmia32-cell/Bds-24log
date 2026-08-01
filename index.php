<?php
// GitHub রিপোজিটরির তথ্য
$repoUser = "mrsohagmia32-cell";
$repoName = "Bds-24log";

// URL থেকে নির্দিষ্ট পোস্টের নাম নেওয়া
$singlePostFile = isset($_GET['post']) ? $_GET['post'] : null;

/**
 * YAML Frontmatter পার্স করার ফাংশন
 */
function parseFrontmatter($content) {
    $metadata = [];
    $body = $content;

    if (preg_match('/^---[\r\n]+([\s\S]*?)[\r\n]+---[\r\n]+([\s\S]*)$/', $content, $matches)) {
        $headerLines = explode("\n", $matches[1]);
        foreach ($headerLines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) {
                $key = trim($parts[0]);
                $value = trim(str_replace(['"', "'"], '', $parts[1]));
                $metadata[$key] = $value;
            }
        }
        $body = trim($matches[2]);
    }

    return ['metadata' => $metadata, 'body' => $body];
}

/**
 * ব্যাকলিংক নো-ফলো এবং ব্র্যাকেট মুক্ত করার সার্ভার-সাইড প্রসেসর
 */
function processLinksAndBody($text) {
    if (empty($text)) return '';

    // ১. [Anchor Text] এবং (URL) এর মাঝে স্পেস/নিউলাইন যাই থাকুক, সেটিকে ক্লিন করে <a> ট্যাগে রূপান্তর
    $pattern = '/\[\s*([^\]]+?)\s*\]\s*[\r\n]*\s*\(\s*(https?:\/\/[^\s\)]+)\s*\)/i';
    $replacement = '<a href="$2" target="_blank" rel="nofollow noopener noreferrer">$1</a>';
    $html = preg_replace($pattern, $replacement, $text);

    // ২. সরাসরি কোনো খালি URL থাকলেও সেটাকে Nofollow লিংক বানানো
    $rawUrlPattern = '/(^|[^\w"\'=>])(https?:\/\/[^\s<]+)/i';
    $html = preg_replace_callback($rawUrlPattern, function($matches) {
        if (strpos($matches[1], 'href=') !== false) {
            return $matches[0];
        }
        return $matches[1] . '<a href="' . $matches[2] . '" target="_blank" rel="nofollow noopener noreferrer">' . $matches[2] . '</a>';
    }, $html);

    // ৩. লাইন ব্রেকগুলোকে <br> ট্যাগে রূপান্তর
    return nl2br($html);
}

/**
 * GitHub API থেকে ফাইল আনার জন্য cURL ফাংশন
 */
function fetchGitHubData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Blog-App');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>আমার ব্লগ</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f9; color: #333; }
    .container { max-width: 800px; margin: 0 auto; }
    .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #ddd; padding-bottom: 15px; }
    h1 { margin: 0; color: #2c3e50; font-size: 24px; }
    .add-post-btn { background-color: #28a745; color: white; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 14px; }
    
    .post-card { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); }
    .post-card img { max-width: 100%; height: auto; border-radius: 6px; margin-bottom: 15px; }
    .post-title { font-size: 22px; margin: 0 0 10px 0; }
    .post-title a { color: #0070f3; text-decoration: none; }
    .post-title a:hover { text-decoration: underline; }
    .post-date { font-size: 0.85em; color: #777; margin-bottom: 12px; }
    .post-summary { line-height: 1.6; color: #555; margin-bottom: 15px; }
    
    .post-body { line-height: 1.8; font-size: 16px; word-wrap: break-word; }
    .post-body a { color: #0070f3 !important; text-decoration: underline !important; font-weight: bold; }
    .post-body a:hover { color: #0056b3 !important; }
    
    .read-more-btn { display: inline-block; background: #0070f3; color: #fff; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-size: 14px; }
    .back-btn { display: inline-block; margin-bottom: 20px; color: #0070f3; text-decoration: none; font-weight: bold; }
  </style>
</head>
<body>

  <div class="container">
    <div class="header-area">
      <h1><a href="index.php" style="text-decoration:none; color:inherit;">আমার ব্লগ</a></h1>
      <a href="/admin/" class="add-post-btn">+ নতুন পোস্ট</a>
    </div>

    <div id="app-content">
      <?php if ($singlePostFile): ?>
        <?php
          // ১. একক পোস্ট লোড করার অংশ (Single Post View)
          $rawUrl = "https://raw.githubusercontent.com/{$repoUser}/{$repoName}/main/posts/" . urlencode($singlePostFile);
          $fileContent = fetchGitHubData($rawUrl);

          if ($fileContent):
              $parsed = parseFrontmatter($fileContent);
              $meta = $parsed['metadata'];
              $body = $parsed['body'];

              $title = isset($meta['title']) ? $meta['title'] : str_replace(['.md', '.html'], '', $singlePostFile);
              $date = isset($meta['date']) ? date("d/m/Y", strtotime($meta['date'])) : '';
              $image = isset($meta['image']) ? $meta['image'] : '';
              
              $formattedBody = processLinksAndBody($body);
        ?>
            <a href="index.php" class="back-btn">&larr; হোমপেজে ফিরে যান</a>
            <div class="post-card">
              <?php if (!empty($image)): ?>
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>">
              <?php endif; ?>
              <h1 style="margin-top:0;"><?php echo htmlspecialchars($title); ?></h1>
              <?php if (!empty($date)): ?>
                <div class="post-date">প্রকাশের তারিখ: <?php echo $date; ?></div>
              <?php endif; ?>
              <div class="post-body"><?php echo $formattedBody; ?></div>
            </div>
          <?php else: ?>
            <p>পোস্টটি পাওয়া যায়নি!</p>
          <?php endif; ?>

      <?php else: ?>
        <?php
          // ২. সব পোস্টের তালিকা লোড করার অংশ (Home View)
          $apiUrl = "https://api.github.com/repos/{$repoUser}/{$repoName}/contents/posts";
          $jsonResponse = fetchGitHubData($apiUrl);
          $files = json_decode($jsonResponse, true);

          if (is_array($files) && !isset($files['message'])):
              $files = array_reverse($files); // সাম্প্রতিক পোস্টগুলো আগে রাখা
              
              foreach ($files as $file):
                  if (isset($file['name']) && (str_ends_with($file['name'], '.md') || str_ends_with($file['name'], '.html'))):
                      $fileContent = fetchGitHubData($file['download_url']);
                      $parsed = parseFrontmatter($fileContent);
                      $meta = $parsed['metadata'];
                      $body = $parsed['body'];

                      $title = isset($meta['title']) ? $meta['title'] : str_replace(['.md', '.html'], '', $file['name']);
                      $date = isset($meta['date']) ? date("d/m/Y", strtotime($meta['date'])) : '';
                      $image = isset($meta['image']) ? $meta['image'] : '';

                      // সামারি তৈরি করা
                      $cleanText = preg_replace('/\[\s*([^\]]+?)\s*\][\s\r\n]*\([^\)]+\)/', '$1', $body);
                      $cleanText = strip_tags($cleanText);
                      $summary = isset($meta['summary']) ? $meta['summary'] : mb_substr($cleanText, 0, 150) . '...';

                      $postUrl = "?post=" . urlencode($file['name']);
        ?>
                      <div class="post-card">
                        <?php if (!empty($image)): ?>
                          <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>">
                        <?php endif; ?>
                        <h2 class="post-title"><a href="<?php echo $postUrl; ?>"><?php echo htmlspecialchars($title); ?></a></h2>
                        <?php if (!empty($date)): ?>
                          <div class="post-date">তারিখ: <?php echo $date; ?></div>
                        <?php endif; ?>
                        <div class="post-summary"><?php echo htmlspecialchars($summary); ?></div>
                        <a href="<?php echo $postUrl; ?>" class="read-more-btn">সম্পূর্ণ পড়ুন &rarr;</a>
                      </div>
        <?php
                  endif;
              endforeach;
          else:
        ?>
              <p>কোনো পোস্ট পাওয়া যায়নি বা ফোল্ডার খালি!</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
