<?php
include_once('./_common.php');
define('_MAIN_', true);

if(defined('G5_THEME_PATH')) {
	require_once(G5_THEME_PATH.'/main.php');
	return;
}
include_once(G5_PATH.'/head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/main.css">', 0);
include_once(G5_PATH."/intro.php");
?>

<div id="main_body">

<?
$main_content = get_site_content('site_main');
if($main_content) { 
	echo $main_content;
} else { 
?>
<?php
    // 캐릭터 데이터 (실제로는 DB에서 가져올 수 있음)
    $characters = [
        [
            'id' => 1,
            'name' => '세라핌',
            'title' => '빛의 수호자',
            'rarity' => 6,
            'class' => '워리어',
            'element' => '빛',
            'image' => 'https://images.unsplash.com/photo-1762008387452-25fe91ab3f90?w=800',
            'description' => '천상계의 빛을 다루는 엘리트 워리어. 강력한 방어력과 치유 능력을 보유하고 있습니다.',
            'hp' => 4500,
            'atk' => 850,
            'def' => 720,
            'spd' => 105,
            'level' => 80
        ],
        [
            'id' => 2,
            'name' => '아르카나',
            'title' => '신비의 마법사',
            'rarity' => 6,
            'class' => '메이지',
            'element' => '물',
            'image' => 'https://images.unsplash.com/photo-1766879240552-1d45e2a5d79f?w=800',
            'description' => '고대 마법의 비밀을 깨우친 천재 마법사. 광역 마법 공격에 특화되어 있습니다.',
            'hp' => 3200,
            'atk' => 1100,
            'def' => 480,
            'spd' => 95,
            'level' => 75
        ],
        [
            'id' => 3,
            'name' => '제로',
            'title' => '사이버 어쌔신',
            'rarity' => 5,
            'class' => '어쌔신',
            'element' => '어둠',
            'image' => 'https://images.unsplash.com/photo-1711662171213-4141abec218f?w=800',
            'description' => '미래에서 온 암살자. 빠른 속도와 치명타로 적을 제압합니다.',
            'hp' => 3500,
            'atk' => 980,
            'def' => 520,
            'spd' => 135,
            'level' => 70
        ],
        [
            'id' => 4,
            'name' => '루나',
            'title' => '달빛 궁수',
            'rarity' => 5,
            'class' => '아처',
            'element' => '바람',
            'image' => 'https://images.unsplash.com/photo-1636075204447-ed932101c622?w=800',
            'description' => '엘프의 숲을 지키는 명궁. 정확한 저격과 기동성이 뛰어납니다.',
            'hp' => 3800,
            'atk' => 920,
            'def' => 550,
            'spd' => 120,
            'level' => 65
        ],
        [
            'id' => 5,
            'name' => '렉스',
            'title' => '철벽의 기사',
            'rarity' => 4,
            'class' => '탱커',
            'element' => '땅',
            'image' => 'https://images.unsplash.com/photo-1710757885750-d93707231791?w=800',
            'description' => '왕국을 지키는 충성스러운 기사. 압도적인 방어력을 자랑합니다.',
            'hp' => 5200,
            'atk' => 650,
            'def' => 890,
            'spd' => 85,
            'level' => 60
        ],
        [
            'id' => 6,
            'name' => '카게',
            'title' => '그림자 닌자',
            'rarity' => 5,
            'class' => '어쌔신',
            'element' => '어둠',
            'image' => 'https://images.unsplash.com/photo-1691390927195-3f3b16849982?w=800',
            'description' => '어둠 속에서 활동하는 은밀한 닌자. 회피와 연속 공격에 특화되어 있습니다.',
            'hp' => 3300,
            'atk' => 1000,
            'def' => 490,
            'spd' => 140,
            'level' => 68
        ]
    ];

    $currentCharacter = $characters[0];
    ?>

    <div class="game-container">
        <!-- Background -->
        <div class="bg-layer">
            <img src="https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?w=1920&q=80" alt="background">
            <div class="bg-overlay"></div>
        </div>

        <!-- Top Bar -->
        <div class="top-bar">
            <div class="user-info">
                <div class="user-avatar">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&h=100&fit=crop" alt="user">
                </div>
                <div class="user-details">
                    <div class="user-name">지휘관</div>
                    <div class="user-level">Lv.82 마스터</div>
                </div>
            </div>

            <div class="resources">
                <!-- Currency 1 -->
                <div class="resource-item">
                    <div class="resource-icon" style="background: linear-gradient(135deg, #60a5fa, #06b6d4);">💎</div>
                    <span class="resource-amount">99,999</span>
                    <button class="resource-add">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>

                <!-- Currency 2 -->
                <div class="resource-item">
                    <div class="resource-icon" style="background: linear-gradient(135deg, #fbbf24, #f97316);">🪙</div>
                    <span class="resource-amount">99,999</span>
                    <button class="resource-add">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>

                <!-- Currency 3 -->
                <div class="resource-item">
                    <div class="resource-icon" style="background: linear-gradient(135deg, #a78bfa, #ec4899);">⭐</div>
                    <span class="resource-amount">99,999</span>
                    <button class="resource-add">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>

                <!-- Action Buttons -->
                <button class="icon-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </button>
                <button class="icon-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Left Sidebar -->
            <div class="left-sidebar">
                <button class="menu-item active">
                    <div class="menu-badge"></div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="8" width="18" height="4" rx="1"></rect>
                        <path d="M12 8v13M17 8l-5 13M7 8l5 13"></path>
                    </svg>
                    <span>이벤트</span>
                </button>

                <button class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87m-4-12a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>캐릭터</span>
                </button>

                <button class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>테스트</span>
                </button>

                <div class="event-banner">
                    <div class="event-label">신규 이벤트</div>
                    <div class="event-version">2.8 업데이트</div>
                </div>

                <button class="notice-btn">
                    <span>공지 1.1V</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
            </div>

            <!-- Center Character -->
            <div class="center-character">
                <div class="character-glow"></div>
                <div class="character-display" id="characterDisplay">
                    <img src="<?php echo $currentCharacter['image']; ?>" alt="<?php echo $currentCharacter['name']; ?>" class="character-img">
                    <div class="character-info">
                        <div class="character-stars">
                            <?php for($i = 0; $i < $currentCharacter['rarity']; $i++): ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#fbbf24" stroke="#fbbf24">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            <?php endfor; ?>
                        </div>
                        <h2 class="character-name"><?php echo $currentCharacter['name']; ?></h2>
                        <p class="character-title"><?php echo $currentCharacter['title']; ?></p>
                        
                        <div class="character-stats">
                            <div class="stat-item">
                                <div class="stat-label">HP</div>
                                <div class="stat-value" style="color: #22d3ee;"><?php echo $currentCharacter['hp']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">ATK</div>
                                <div class="stat-value" style="color: #f87171;"><?php echo $currentCharacter['atk']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">DEF</div>
                                <div class="stat-value" style="color: #4ade80;"><?php echo $currentCharacter['def']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">SPD</div>
                                <div class="stat-value" style="color: #facc15;"><?php echo $currentCharacter['spd']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <!-- Story Card -->
                <div class="info-card story-card" onclick="showCharacterModal(<?php echo $characters[1]['id']; ?>)">
                    <div class="new-badge">NEW</div>
                    <img src="<?php echo $characters[1]['image']; ?>" alt="story" class="card-bg">
                    <div class="card-overlay"></div>
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-label">STORY</div>
                            <div class="card-title">Chapter 4-3 숙청의 날</div>
                        </div>
                        <div class="card-action">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Alert Card -->
                <div class="info-card alert-card">
                    <div class="card-icon" style="background: rgba(239, 68, 68, 0.2);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="card-text">
                        <div class="card-label">긴급 임무</div>
                        <div class="card-title">위기</div>
                    </div>
                </div>

                <!-- Diary Card -->
                <div class="info-card diary-card">
                    <div class="card-icon" style="background: rgba(168, 85, 247, 0.2);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="card-text">
                        <div class="card-label">암호 일지</div>
                        <div class="card-title">SECRET DIARY</div>
                    </div>
                </div>

                <!-- Recruit Card -->
                <div class="info-card recruit-card" onclick="showCharacterModal(<?php echo $characters[2]['id']; ?>)">
                    <div class="shop-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        Shop
                    </div>
                    <img src="<?php echo $characters[2]['image']; ?>" alt="recruit" class="card-bg">
                    <div class="card-overlay"></div>
                    <div class="card-content">
                        <div class="card-label" style="color: #fbbf24;">Recruit</div>
                        <div class="card-title">모집합니다</div>
                    </div>
                </div>

                <!-- Collection Card -->
                <div class="info-card collection-card">
                    <div class="collection-header">
                        <div class="collection-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22d3ee" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87m-4-12a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span>캐릭터 컬렉션</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                    <div class="collection-grid">
                        <?php for($i = 1; $i <= 3; $i++): ?>
                        <div class="collection-item" onclick="showCharacterModal(<?php echo $characters[$i]['id']; ?>)">
                            <img src="<?php echo $characters[$i]['image']; ?>" alt="character">
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <button class="nav-item active">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                </svg>
                <span>홈</span>
            </button>
            <button class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <circle cx="12" cy="12" r="6"></circle>
                    <circle cx="12" cy="12" r="2"></circle>
                </svg>
                <span>전투</span>
            </button>
            <button class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <span>임무</span>
            </button>
            <button class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                <span>수집</span>
            </button>
            <button class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                </svg>
                <span>동료</span>
            </button>
            <button class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                </svg>
                <span>상점</span>
            </button>
            <button class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>정보</span>
            </button>
        </div>
    </div>

    <!-- Character Modal -->
    <div id="characterModal" class="modal">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        // 캐릭터 데이터를 JavaScript로 전달
        const characters = <?php echo json_encode($characters); ?>;

        // 캐릭터 모달 표시
        function showCharacterModal(characterId) {
            const character = characters.find(c => c.id === characterId);
            if (!character) return;

            const modal = document.getElementById('characterModal');
            const modalBody = document.getElementById('modalBody');
            
            let starsHtml = '';
            for(let i = 0; i < character.rarity; i++) {
                starsHtml += `<svg width="20" height="20" viewBox="0 0 24 24" fill="#fbbf24" stroke="#fbbf24">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>`;
            }

            modalBody.innerHTML = `
                <div class="modal-header">
                    <img src="${character.image}" alt="${character.name}" class="modal-header-img">
                    <div class="modal-header-overlay"></div>
                    <div class="modal-header-content">
                        <div class="modal-stars">${starsHtml}</div>
                        <h2 class="modal-character-name">${character.name}</h2>
                        <p class="modal-character-title">${character.title}</p>
                        <button class="favorite-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="modal-tags">
                        <div class="modal-tag">
                            <div class="tag-label">클래스</div>
                            <div class="tag-value">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                </svg>
                                <span>${character.class}</span>
                            </div>
                        </div>
                        <div class="modal-tag">
                            <div class="tag-label">속성</div>
                            <div class="tag-value">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                                <span>${character.element}</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-description">
                        <h3>캐릭터 정보</h3>
                        <p>${character.description}</p>
                    </div>

                    <div class="modal-stats">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22d3ee" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            스탯
                        </h3>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-box-header">
                                    <span>체력 (HP)</span>
                                    <span class="stat-box-value" style="color: #22d3ee;">${character.hp}</span>
                                </div>
                                <div class="stat-bar">
                                    <div class="stat-bar-fill hp-bar" style="width: ${(character.hp / 6000) * 100}%"></div>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-box-header">
                                    <span>공격력 (ATK)</span>
                                    <span class="stat-box-value" style="color: #f87171;">${character.atk}</span>
                                </div>
                                <div class="stat-bar">
                                    <div class="stat-bar-fill atk-bar" style="width: ${(character.atk / 1200) * 100}%"></div>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-box-header">
                                    <span>방어력 (DEF)</span>
                                    <span class="stat-box-value" style="color: #4ade80;">${character.def}</span>
                                </div>
                                <div class="stat-bar">
                                    <div class="stat-bar-fill def-bar" style="width: ${(character.def / 1000) * 100}%"></div>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-box-header">
                                    <span>속도 (SPD)</span>
                                    <span class="stat-box-value" style="color: #facc15;">${character.spd}</span>
                                </div>
                                <div class="stat-bar">
                                    <div class="stat-bar-fill spd-bar" style="width: ${(character.spd / 150) * 100}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button class="btn-primary">획득하기</button>
                        <button class="btn-secondary">상세 정보</button>
                    </div>
                </div>
            `;

            modal.classList.add('show');
            document.body.style.overflow = 'hidden';

            // 애니메이션 트리거
            setTimeout(() => {
                const bars = modalBody.querySelectorAll('.stat-bar-fill');
                bars.forEach(bar => bar.style.opacity = '1');
            }, 100);
        }

        function closeModal() {
            const modal = document.getElementById('characterModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // 초기 애니메이션
        window.addEventListener('load', () => {
            document.querySelectorAll('.top-bar, .menu-item, .character-display, .info-card, .nav-item').forEach((el, i) => {
                el.style.animation = `fadeInUp 0.5s ease ${i * 0.05}s forwards`;
                el.style.opacity = '0';
            });
        });
    </script>
<?php } ?>
</div>

<script>
$(function() { 
	window.onload = function() {
		$('#body').css('opacity', 1);
	};
});
</script>

<?
include_once(G5_PATH.'/tail.php');
?>