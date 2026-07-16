<?php
header('Content-Type: application/json; charset=utf-8');

$query = $_GET['q'] ?? '';
if (empty($query)) {
    echo json_encode(['results' => []]);
    exit;
}

// Scrape YouTube for music videos matching query
$url = 'https://www.youtube.com/results?search_query=' . urlencode($query . " music");
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);  // fail fast if can't connect
curl_setopt($ch, CURLOPT_TIMEOUT, 6);         // max 6 sec total
$html = curl_exec($ch);
curl_close($ch);

$results = [];

if ($html) {
    // Extract ytInitialData json from the page source
    if (preg_match('/ytInitialData\s*=\s*({.*?});/', $html, $matches)) {
        $jsonStr = $matches[1];
        $data = json_decode($jsonStr, true);
        
        try {
            // Traverse the YouTube JSON layout
            $contents = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'][0]['itemSectionRenderer']['contents'];
            
            foreach ($contents as $item) {
                if (isset($item['videoRenderer'])) {
                    $video = $item['videoRenderer'];
                    $videoId = $video['videoId'];
                    $title = $video['title']['runs'][0]['text'] ?? '';
                    $duration = $video['lengthText']['simpleText'] ?? '0:00';
                    $author = $video['ownerText']['runs'][0]['text'] ?? '';
                    $thumbnail = $video['thumbnail']['thumbnails'][0]['url'] ?? '';
                    if (substr($thumbnail, 0, 2) === '//') {
                        $thumbnail = 'https:' . $thumbnail;
                    }
                    // Always provide a reliable hqdefault fallback
                    if (empty($thumbnail)) {
                        $thumbnail = 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg';
                    }
                    
                    // Filter out long live streams or playlist aggregates
                    if (strpos($duration, ':') !== false) {
                        $results[] = [
                            'id' => $videoId,
                            'title' => $title,
                            'author' => $author,
                            'duration' => $duration,
                            'thumbnail' => $thumbnail
                        ];
                    }
                    
                    if (count($results) >= 20) {
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            // Silent catch, return whatever is collected
        }
    }
}

echo json_encode(['results' => $results]);
