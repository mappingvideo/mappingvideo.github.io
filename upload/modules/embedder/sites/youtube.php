<?php
set_time_limit(0);
class MEmbed_youtube {
    public $url;
    public $user_id;
    public $category;
    public $status;
    public $video;

    public $errors = array();
    public $message;

    private $overflow = 500;

    public $video_already = 0;
    public $video_added = 0;
    public function __construct($url, $user_id, $category, $status)
    {
        $this->url = $url;
        $this->user_id = $user_id;
        $this->category = $category;
        $this->status = $status;
    }

    public function get_videos()
    {
        $count = 0;

        $html = file_get_contents($this->url);
        // echo $html;
        if ($html) {
            preg_match_all('/watch\\?v=(.+?)"/', $html, $match);
            $ytids = array();
            foreach ($match[1] as $mm) {
                $temp = explode('&', $mm);
                if (!empty($temp[0])) {
                    $ytids[] = $temp[0];
                }
            }
            $ytids = array_unique($ytids);
            // print_r($ytids);
            if (!empty($ytids)) {
                foreach ($ytids as $yid) {
                    $video = $this->getVideoInfo($yid);
                    if (!empty($video['title'])) {
                        if (already_added('youtube', $video['url'])) {
                            ++$this->video_already;
                            continue;
                        }

                        if (add_video($video)) {
                            ++$this->video_added;
                        } else {
                            $this->errors[] = 'Failed to add ' . $video['url'] . '!';
                        }
                    }
                }
            } else {
                $this->errors[] = 'Failed to get html code for specified url!';
            }

            if (!$this->errors) {
                return true;
            }
            return false;
        }
    }
    function getVideoInfo($id)
    {
        $video = array(
            'user_id' => $this->user_id,
            'site' => 'youtube',
            'url' => '',
            'title' => '',
            'desc' => '',
            'tags' => '',
            'category' => '',
            'thumbs' => array(),
            'duration' => 0,
            'embed' => '',
            'size' => 0,
            'file_url' => '',
            'status' => $this->status
            );

        $json = file_get_contents('https://gdata.youtube.com/feeds/api/videos?alt=json&q=' . urlencode($id));
        $json = json_decode($json, true);

        $entry = @$json['feed']['entry'][0];
        $video['url'] = 'https://www.youtube.com/watch?v=' . $id;
        $video['title'] = $entry['title']['$t'];
        $video['desc'] = $entry['content']['$t'];
        $video['tags'] = $entry['media$group']['media$keywords']['$t'];
        $video['category'] = $entry['media$group']['media$category'][0]['label'];
        $video['thumbs'] = array('http://img.youtube.com/vi/' . $id . '/0.jpg');
        $video['duration'] = $entry['media$group']['yt$duration']['seconds'];
        $video['embed'] = '<iframe class="youtube-player" type="text/html" width="' . E_WIDTH . '" height="' . E_HEIGHT . '" src="http://www.youtube.com/embed/' . $id . '" frameborder="0"></iframe>';
        return $video;
    }
}

?>