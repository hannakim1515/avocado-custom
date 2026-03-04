<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
?>

 <!-- 로딩 스크린 -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading...</div>
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
        </div>
    </div> 

    <!-- 메인 컨테이너 -->
    <div id="mainContent" class="main-container" style="display: none;">
        <!-- 상단 헤더 -->
        <header class="header">
            <div class="header-left">
                <div class="avatar"></div>
                <div>
                    <div class="header-title">지휘관</div>
                    <div class="header-subtitle">Lv. 82</div>
                    <? include(G5_PATH."/templete/txt.outlogin.php"); ?>
                </div>
            </div>
            
            <div class="header-right">
                <div class="currency-box">
                    <div class="currency-icon emerald"></div>
                    <span class="currency-value">99,999</span>
                </div>
                <div class="currency-box">
                    <div class="currency-icon teal"></div>
                    <span class="currency-value">32,545</span>
                </div>
                <div class="currency-box desktop-only">
                    <div class="currency-icon cyan"></div>
                    <span class="currency-value">198</span>
                </div>
            </div>
        </header>

        <!-- 메인 콘텐츠 -->
        <div class="main-content">
            <!-- 중앙 캐릭터 섹션 -->
            <div class="character-section">
                <!-- 말풍선 -->
                <div id="speechBubble" class="speech-bubble">
                    <div class="speech-bubble-inner">
                        <div class="speech-overlay"></div>
                        <p class="speech-text">
                            <span id="typedText"></span>
                            <span id="cursor" class="cursor"></span>
                        </p>
                        <div class="speech-tail">
                            <div class="speech-tail-inner"></div>
                        </div>
                    </div>
                </div>

                <!-- 캐릭터 -->
                <div class="character-container">
                    <img src="https://i.imgur.com/MUMewZS.png" alt="Character" class="character-img">
                </div>

                <!-- 장식 요소 -->
                <div class="decoration-glow"></div>
            </div>

            <!-- 하단 메뉴 -->
            <div class="side-menu">
                <div class="menu-container">
                    <button class="h-menu-item active" data-menu="home">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span class="menu-label">홈</span>
                    </button>
                    <button class="h-menu-item" data-menu="battle">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                        <span class="menu-label">전투</span>
                    </button>
                    <button class="h-menu-item" data-menu="story">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        <span class="menu-label">스토리</span>
                    </button>
                    <button class="h-menu-item" data-menu="characters">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="menu-label">캐릭터</span>
                    </button>
                    <button class="h-menu-item" data-menu="shop">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 12 20 22 4 22 4 12"></polyline>
                            <rect x="2" y="7" width="20" height="5"></rect>
                            <line x1="12" y1="22" x2="12" y2="7"></line>
                            <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                            <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                        </svg>
                        <span class="menu-label">상점</span>
                    </button>
                    <button class="h-menu-item" data-menu="event">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span class="menu-label">이벤트</span>
                    </button>
                    <button class="h-menu-item" data-menu="settings">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 1v6m0 6v6m-6-6h6m6 0h-6m-2.8 8.2l4.2-4.2m0 0l4.2 4.2M4.93 4.93l4.24 4.24m0 0l4.24-4.24"></path>
                        </svg>
                        <span class="menu-label">설정</span>
                    </button>
                </div>
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
            setTimeout(function() {
                document.getElementById('loadingScreen').style.opacity = '0';
                setTimeout(function() {
                    document.getElementById('loadingScreen').style.display = 'none';
                    document.getElementById('mainContent').style.display = 'block';
                    initApp();
                }, 500);
            }, 2000);
        });

        function initApp() {
            // 타이핑 효과
            const greetings = [
                '어서와요 요한.',
                '아~ 해보세요!',
                '준비 됐어요?'
            ];
            
            const greeting = greetings[Math.floor(Math.random() * greetings.length)];
            const typedText = document.getElementById('typedText');
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