<?php
include_once('../common.php');
include G5_PATH . '/k_battle/_raid_common.php';

// 쿼리스트링 유지
$query_string = $_SERVER['QUERY_STRING'];
$iframe_src = G5_URL . '/k_battle/raid.inc.php?' . $query_string . '&in_frame=1';

// 레이드 정보 조회 (배경음악 설정용)
$ra_id = ses($_REQUEST, 'ra_id', '', 'string');
$raid_type = ses($_REQUEST, 'raid_type', 'realtime', 'string');

$bgm_video_id = '';
$bgm_volume = 30;

// 레이드별 BGM 설정 조회
$bgm_type = 'single'; // 기본값
if ($battle_table) {
    $ra = sql_fetch("SELECT ra_bgm, ra_bgm_volume, ra_bgm_type, ra_title FROM {$battle_table} WHERE ra_id = '" . sql_escape_string($ra_id) . "'");
    if (!empty($ra['ra_bgm'])) {
        $bgm_video_id = $ra['ra_bgm'];
        $bgm_volume = ses($ra, 'ra_bgm_volume', 30, 'int');
        $bgm_type = ses($ra, 'ra_bgm_type', 'single', 'raw');
    }
}

// ra_bgm_type에 따라 플레이리스트 여부 결정
$is_playlist = ($bgm_type === 'list');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($ra['ra_title']) ? h($ra['ra_title']) : '레이드'; ?></title>
    <style>
        @font-face {
            font-family: 'material';
            font-style: normal;
            font-weight: 400;
            src: url(https://fonts.gstatic.com/s/materialicons/v140/flUhRq6tzZclQEJ-Vdg-IuiaDsNc.woff2) format('woff2');
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; background: #000; }
        
        .raid-frame {
            width: 100%;
            height: 100%;
            border: none;
            padding:20px 0;
        }
        

        .bgm-controls {
            position: fixed;
            top: 5px;
            right: 15px;
            z-index: 9999;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            height: 20px;
        }
        
        .bgm-controls.hidden {
            display: none;
        }
        
        .bgm-controls button {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            font-family: 'material';
        }
        
        .bgm-controls button:hover {
            opacity: 0.8;
        }
        
        #youtube-player {
            position: fixed;
            bottom: 0px;
            right: 0px;
            width: 1px;
            height: 1px;
            display:none;
        }
    </style>
</head>
<body>

<!-- YouTube 플레이어 -->
<div id="youtube-player"></div>

<!-- BGM 컨트롤 -->
<div class="bgm-controls<?php echo empty($bgm_video_id) ? ' hidden' : ''; ?>">
    <button id="bgm-play" onclick="playBGM()" title="재생">play_arrow</button>
    <button id="bgm-pause" onclick="pauseBGM()" title="일시정지">pause</button>
    <button id="bgm-stop" onclick="stopBGM()" title="정지">stop</button>
</div>

<!-- 레이드 콘텐츠 iframe -->
<iframe id="raid-iframe" class="raid-frame" src="<?php echo h($iframe_src); ?>"></iframe>

<?php if (!empty($bgm_video_id)): ?>
<!-- YouTube IFrame API -->
<script src="https://www.youtube.com/iframe_api"></script>
<script>
var player;
var isPlaying = false;
var isMinimized = false;
var bgmVideoId = '<?php echo h($bgm_video_id); ?>';
var isPlaylist = <?php echo $is_playlist ? 'true' : 'false'; ?>;
var defaultVolume = <?php echo (int)$bgm_volume; ?>;

function onYouTubeIframeAPIReady() {
    
    var playerConfig = {
        height: '160',
        width: '280',
        events: {
            onReady: onPlayerReady,
            onStateChange: onPlayerStateChange,
            onError: onPlayerError
        }
    };
    
    // 재생목록인 경우
    if (isPlaylist) {
        playerConfig.playerVars = {
            listType: 'playlist',
            list: bgmVideoId,
            autoplay: 1,
            loop: 1,
            controls: 1,
            modestbranding: 1,
            rel: 0
        };
    } else {
        // 일반 영상인 경우
        playerConfig.videoId = bgmVideoId;
        playerConfig.playerVars = {
            autoplay: 1,
            loop: 1,
            playlist: bgmVideoId,
            controls: 1,
            modestbranding: 1,
            rel: 0
        };
    }
    
    player = new YT.Player('youtube-player', playerConfig);
}

function onPlayerReady(event) {
    event.target.setVolume(defaultVolume);
    event.target.playVideo();
    isPlaying = true;
}

function onPlayerStateChange(event) {
    if (event.data === YT.PlayerState.ENDED) {
        if (!isPlaylist) {
            player.playVideo();
        }
    }
    if (event.data === YT.PlayerState.PLAYING) {
        isPlaying = true;
    }
    if (event.data === YT.PlayerState.PAUSED) {
        isPlaying = false;
    }
}

function onPlayerError(event) {
    // 에러 발생 시 컨트롤 숨김
}

function playBGM() {
    if (!player || !player.playVideo) return;
    player.playVideo();
}

function pauseBGM() {
    if (!player || !player.pauseVideo) return;
    player.pauseVideo();
}

function stopBGM() {
    if (!player || !player.stopVideo) return;
    player.stopVideo();
    isPlaying = false;
}

var isMuted = false;
var lastVolume = defaultVolume;

function toggleMute() {
    if (!player || !player.mute) return;
    
    if (isMuted) {
        player.unMute();
        player.setVolume(lastVolume);
        document.getElementById('bgm-volume').value = lastVolume;
    } else {
        lastVolume = player.getVolume();
        player.mute();
    }
    isMuted = !isMuted;
    updateButtons();
}

function togglePlayerView() {
    var playerDiv = document.getElementById('youtube-player');
    var btn = document.getElementById('bgm-minimize');
    
    if (isMinimized) {
        playerDiv.classList.remove('minimized');
        btn.textContent = '📺';
    } else {
        playerDiv.classList.add('minimized');
        btn.textContent = '🔲';
    }
    isMinimized = !isMinimized;
}

function updateButtons() {
    var muteBtn = document.getElementById('bgm-mute');
    muteBtn.textContent = isMuted ? '🔇' : '🔊';
}

function setVolume(vol) {
    if (player && player.setVolume) {
        player.setVolume(parseInt(vol));
        lastVolume = parseInt(vol);
        if (isMuted && vol > 0) {
            player.unMute();
            isMuted = false;
            updateButtons();
        }
    }
}

// iframe 내부에서 새로고침 요청 시 처리
window.addEventListener('message', function(e) {
    if (e.data === 'reload-raid') {
        document.getElementById('raid-iframe').contentWindow.location.reload();
    }
    if (e.data === 'bgm-play' && player) {
        player.playVideo();
    }
    if (e.data === 'bgm-pause' && player) {
        player.pauseVideo();
    }
});

// F5 키 누르면 자식 프레임만 새로고침
window.addEventListener('keydown', function(e) {
    if (e.key === 'F5' || e.keyCode === 116) {
        e.preventDefault();
        document.getElementById('raid-iframe').contentWindow.location.reload();
    }
});
</script>
<?php else: ?>
<script>
// BGM 없을 때도 iframe 새로고침 메시지 처리
window.addEventListener('message', function(e) {
    if (e.data === 'reload-raid') {
        document.getElementById('raid-iframe').contentWindow.location.reload();
    }
});

// F5 키 누르면 자식 프레임만 새로고침
window.addEventListener('keydown', function(e) {
    if (e.key === 'F5' || e.keyCode === 116) {
        e.preventDefault();
        document.getElementById('raid-iframe').contentWindow.location.reload();
    }
});
</script>
<?php endif; ?>

</body>
</html>
