<?php
  $apiUrl = "https://api.github.com/repos/{$repoUser}/{$repoName}/contents/posts";
  $jsonResponse = fetchGitHubData($apiUrl);
  $files = json_decode($jsonResponse, true);

  if (is_array($files) && !isset($files['message'])):
      
      // প্রতিবার রিফ্রেশে পোস্টগুলো এলোমেলো (Randomize) করা
      shuffle($files);
      
      foreach ($files as $file):
          if (isset($file['name']) && (str_ends_with($file['name'], '.md') || str_ends_with($file['name'], '.html'))):
              $fileContent = fetchGitHubData($file['download_url']);
              $parsed = parseFrontmatter($fileContent);
              $meta = $parsed['metadata'];
              $body = $parsed['body'];

              $title = isset($meta['title']) ? $meta['title'] : str_replace(['.md', '.html'], '', $file['name']);
              $date = isset($meta['date']) ? date("d/m/Y", strtotime($meta['date'])) : '';
              $image = isset($meta['image']) ? $meta['image'] : '';

              $cleanText = preg_replace('/\[\s*([\s\S]*?)\s*\]\s*[\r\n]*\s*\([^\)]+\)/su', '$1', $body);
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
      <p>কোনো পোস্ট পাওয়া যায়নি!</p>
<?php endif; ?>
