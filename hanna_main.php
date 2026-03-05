<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// avo_character 테이블에서 랜덤하게 캐릭터 1명 추출 (ID와 전신이미지)
$sql_char = " SELECT ch_id, ch_body, ch_name 
               FROM avo_character 
               WHERE ch_state = '승인' 
               ORDER BY RAND() 
               LIMIT 1 ";
$char_row = sql_fetch($sql_char);

// 추출된 캐릭터 정보 변수 담기
$selected_ch_id = $char_row['ch_id'];   // 추출된 캐릭터의 고유 ID
$char_img       = $char_row['ch_body']; // 추출된 캐릭터의 전신 이미지 URL

// [단계 2] 위에서 뽑힌 캐릭터 ID를 기준으로 avo_article_value 테이블에서 'txt' 검색
$sql_val = " SELECT av_value 
              FROM avo_article_value 
              WHERE ch_id = '{$selected_ch_id}' 
                AND ar_code = 'txt' ";
$val_row = sql_fetch($sql_val);

// 추출된 한마디 변수 담기
$char_talk = $val_row['av_value'];

// [단계 3] 데이터가 없을 경우를 대비한 기본값 처리
if (!$char_img)  $char_img  = "";
if (!$char_talk) $char_talk = "등록된 한마디가 없습니다.";
?>

    <!-- 메인 컨테이너 -->
    <div id="mainContent" class="main-container" style="display: none;">
        <!-- 상단 헤더 -->
        <header class="header">
            <div class="header-left">
                <div class="avatar"></div>
                <div>
                    <? include(G5_PATH."/templete/txt.outlogin.php"); ?>
                </div>
            </div>
        </header>

        <!-- 메인 콘텐츠 -->
        <div class="main-content">
            <!-- 중앙 캐릭터 섹션 -->
            <div class="character-section">
                <div id="speechBubble" class="speech-bubble">
                    <div class="speech-bubble-inner">
                        <div class="speech-overlay"></div>
                        <p class="speech-text">
                            <span id="typedText" data-msg="<?php echo htmlspecialchars($char_talk); ?>"></span>
                            <span id="cursor" class="cursor"></span>
                        </p>
                        <div class="speech-tail">
                            <div class="speech-tail-inner"></div>
                        </div>
                    </div>
                </div>

                <div class="character-container">
                    <img src="<?php echo $char_img; ?>" alt="<?php echo $row['ch_name']; ?>" class="character-img">
                </div>

                <!-- 장식 요소 -->
                <div class="decoration-glow"></div>
            </div>

            <!-- 데스크탑: 왼쪽 패널들 -->
            <div class="left-panels desktop-only">
                <!-- 스토리 챕터 -->
                <div class="panel-card story-card">
                    <div class="panel-header emerald-gradient">
                        <div class="panel-header-overlay"></div>
                        <div class="panel-header-content">
                            <div>
                                <div class="panel-subtitle">MAIN STORY</div>
                                <div class="panel-title">제 5-3 챕터</div>
                            </div>
                            <div class="progress-badge">50%</div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <p class="panel-text">새로운 이야기가 기다립니다</p>
                        <svg class="chevron-icon emerald" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </div>

                <!-- 일일 미션 -->
                <div class="panel-card mission-card">
                    <div class="panel-mission-header">
                        <div class="panel-icon-container">
                            <div class="panel-icon emerald-gradient">
                                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div>
                                <span class="panel-mission-title">일일 미션</span>
                                <div class="panel-mission-subtitle">오늘의 목표를 달성하세요</div>
                            </div>
                        </div>
                        <svg class="chevron-icon gray" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                    <div class="mission-progress">
                        <div class="mission-progress-text">
                            <span>진행률</span>
                            <span class="mission-progress-value">5/10</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar"></div>
                        </div>
                    </div>
                </div>

                <!-- 특별 이벤트 -->
                <div class="panel-card event-card">
                    <div class="event-content">
                        <div class="panel-icon-container">
                            <div class="panel-icon emerald-gradient">
                                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                            </div>
                            <div>
                                <div class="event-badge">LIMITED</div>
                                <div class="event-title">특별 임무</div>
                            </div>
                        </div>
                        <div class="new-badge">NEW</div>
                    </div>
                </div>
            </div>

            <!-- 데스크탑: 오른쪽 패널들 -->
            <div class="right-panels desktop-only">
                <!-- 캐릭터 모집 -->
                <div class="panel-card recruit-card">
                    <div class="panel-header teal-gradient">
                        <div class="panel-header-overlay animated"></div>
                        <div class="panel-header-content">
                            <div>
                                <div class="panel-subtitle">RECRUIT</div>
                                <div class="panel-title">동료 모집</div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <p class="panel-text">새로운 동료를 영입하세요</p>
                        <svg class="chevron-icon teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </div>

                <!-- 랭킹 -->
                <div class="panel-card ranking-card">
                    <div class="panel-mission-header">
                        <div class="panel-icon-container">
                            <div class="panel-icon emerald-gradient">
                                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                                    <path d="M4 22h16"></path>
                                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="panel-mission-title">랭킹</span>
                                <div class="panel-mission-subtitle">현재 순위: #127</div>
                            </div>
                        </div>
                        <svg class="chevron-icon gray" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                    <div class="ranking-stats">
                        <div class="ranking-stat">
                            <div class="ranking-value emerald">12</div>
                            <div class="ranking-label">승리</div>
                        </div>
                        <div class="ranking-divider"></div>
                        <div class="ranking-stat">
                            <div class="ranking-value gray">3</div>
                            <div class="ranking-label">패배</div>
                        </div>
                        <div class="ranking-divider"></div>
                        <div class="ranking-stat">
                            <div class="ranking-value teal">80%</div>
                            <div class="ranking-label">승률</div>
                        </div>
                    </div>
                </div>

                <!-- 지역 탐험 -->
                <div class="panel-card exploration-card">
                    <div class="exploration-content">
                        <div>
                            <div class="panel-subtitle white">EXPLORATION</div>
                            <div class="exploration-title">지역 탐험</div>
                        </div>
                        <svg class="exploration-icon" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                            <line x1="8" y1="2" x2="8" y2="18"></line>
                            <line x1="16" y1="6" x2="16" y2="22"></line>
                        </svg>
                    </div>
                </div>

                <!-- 이벤트 배너들 -->
                <div class="panel-grid">
                    <div class="small-panel">
                        <div class="panel-icon emerald-gradient">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        </div>
                        <div class="small-panel-title">이벤트</div>
                        <div class="small-panel-subtitle">진행중</div>
                    </div>

                    <div class="small-panel">
                        <div class="panel-icon teal-gradient">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <polyline points="20 12 20 22 4 22 4 12"></polyline>
                                <rect x="2" y="7" width="20" height="5"></rect>
                                <line x1="12" y1="22" x2="12" y2="7"></line>
                                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                            </svg>
                        </div>
                        <div class="small-panel-title">보상</div>
                        <div class="small-panel-subtitle">수령 가능</div>
                    </div>
                </div>
            </div>

            <!-- 모바일: 상단 패널들 -->
            <div class="top-panels mobile-only">
                <div class="mobile-story-panel">
                    <div class="mobile-story-content emerald-gradient">
                        <div>
                            <div class="panel-subtitle">MAIN STORY</div>
                            <div class="mobile-story-title">제 5-3 챕터</div>
                        </div>
                        <div class="progress-badge">50%</div>
                    </div>
                </div>

                <div class="mobile-mission-panel">
                    <div class="mobile-mission-content">
                        <div class="panel-icon emerald-gradient small">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="mobile-mission-title">일일 미션</div>
                            <div class="mobile-mission-subtitle">5/10 완료</div>
                        </div>
                    </div>
                    <div class="mobile-progress-bar">
                        <div class="mobile-progress-fill"></div>
                    </div>
                </div>
            </div>

            <!-- 모바일: 하단 패널들 -->
            <div class="bottom-panels mobile-only">
                <div class="mobile-grid">
                    <div class="mobile-card">
                        <div class="panel-icon teal-gradient small">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                                <path d="M4 22h16"></path>
                                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                                <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path>
                            </svg>
                        </div>
                        <div class="mobile-card-title">랭킹</div>
                        <div class="mobile-card-subtitle">#127</div>
                    </div>

                    <div class="mobile-card">
                        <div class="panel-icon cyan-gradient small">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                                <line x1="8" y1="2" x2="8" y2="18"></line>
                                <line x1="16" y1="6" x2="16" y2="22"></line>
                            </svg>
                        </div>
                        <div class="mobile-card-title">탐험</div>
                        <div class="mobile-card-subtitle">시작</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 로딩 스크린
        window.addEventListener('load', function() {
                    document.getElementById('mainContent').style.display = 'block';
                    initApp();
               
        });

        function initApp() {
            const typedText = document.getElementById('typedText');
            const greeting = typedText.getAttribute('data-msg'); // PHP에서 넘어온 한마디
            const cursor = document.getElementById('cursor');
            const speechBubble = document.getElementById('speechBubble');
            
            setTimeout(function() {
                speechBubble.style.opacity = '1';
                speechBubble.style.transform = 'scale(1) translateY(0)';
                
                let index = 0;
                const typingInterval = setInterval(function() {
                    if (index < greeting.length) {
                        typedText.textContent = greeting.slice(0, index + 1);
                        index++;
                    } else {
                        cursor.style.display = 'none';
                        clearInterval(typingInterval);
                    }
                }, 80);
        }, 500);

            // 진행률 바 애니메이션
            setTimeout(function() {
                const progressBar = document.querySelector('.progress-bar');
                const mobileProgressFill = document.querySelector('.mobile-progress-fill');
                if (progressBar) progressBar.style.width = '50%';
                if (mobileProgressFill) mobileProgressFill.style.width = '50%';
            }, 800);

            // 메뉴 클릭 이벤트
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    menuItems.forEach(function(mi) {
                        mi.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });

            // 패널 애니메이션 (수정본)
            setTimeout(function() {
                const panels = document.querySelectorAll('.panel-card, .small-panel, .panel-grid, .mobile-story-panel, .mobile-mission-panel, .mobile-card');
                
                panels.forEach(function(panel, index) {
                    setTimeout(function() {
                        // 1. 공통적으로 모든 패널을 보이게 합니다.
                        panel.style.opacity = '1';
                        
                        // 2. 회전 로직 적용
                        let rotation = '0deg';
                        if (panel.closest('.left-panels')) {
                            rotation = '30deg';
                        } else if (panel.closest('.right-panels')) {
                            rotation = '-30deg';
                        }

                        // [핵심] small-panel은 직접 회전시키지 않습니다. (부모인 panel-grid가 회전하므로)
                        if (panel.classList.contains('small-panel')) {
                            panel.style.transform = 'translateY(0) translateX(0)'; 
                        } else {
                            // 그 외의 일반 패널이나 부모 그리드는 회전을 적용합니다.
                            panel.style.transform = `translateY(0) translateX(0) rotateY(${rotation})`;
                        }
                        
                    }, index * 100);
                });
            }, 300);
}
    </script>