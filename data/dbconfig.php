<?php
if (!defined('_GNUBOARD_')) exit;
define('G5_MYSQL_HOST', 'localhost');
define('G5_MYSQL_USER', 'root');
define('G5_MYSQL_PASSWORD', '0000');
define('G5_MYSQL_DB', 'test');
define('G5_MASTER_PW', '');
define('G5_DB_URL', 'http://localhost/phpmyadmin/');
define('G5_MYSQL_SET_MODE', true);

define('G5_TABLE_PREFIX', 'avo_');

$g5['write_prefix'] = G5_TABLE_PREFIX.'write_'; // 게시판 테이블명 접두사

$g5['auth_table'] = G5_TABLE_PREFIX.'auth'; // 관리권한 설정 테이블
$g5['config_table'] = G5_TABLE_PREFIX.'config'; // 기본환경 설정 테이블
$g5['group_table'] = G5_TABLE_PREFIX.'group'; // 게시판 그룹 테이블
$g5['group_member_table'] = G5_TABLE_PREFIX.'group_member'; // 게시판 그룹+회원 테이블
$g5['board_table'] = G5_TABLE_PREFIX.'board'; // 게시판 설정 테이블
$g5['board_file_table'] = G5_TABLE_PREFIX.'board_file'; // 게시판 첨부파일 테이블
$g5['board_good_table'] = G5_TABLE_PREFIX.'board_good'; // 게시물 추천,비추천 테이블
$g5['board_new_table'] = G5_TABLE_PREFIX.'board_new'; // 게시판 새글 테이블
$g5['login_table'] = G5_TABLE_PREFIX.'login'; // 로그인 테이블 (접속자수)
$g5['mail_table'] = G5_TABLE_PREFIX.'mail'; // 회원메일 테이블
$g5['member_table'] = G5_TABLE_PREFIX.'member'; // 회원 테이블
$g5['memo_table'] = G5_TABLE_PREFIX.'memo'; // 메모 테이블
$g5['poll_table'] = G5_TABLE_PREFIX.'poll'; // 투표 테이블
$g5['poll_etc_table'] = G5_TABLE_PREFIX.'poll_etc'; // 투표 기타의견 테이블
$g5['point_table'] = G5_TABLE_PREFIX.'point'; // 포인트 테이블
$g5['popular_table'] = G5_TABLE_PREFIX.'popular'; // 인기검색어 테이블
$g5['scrap_table'] = G5_TABLE_PREFIX.'scrap'; // 게시글 스크랩 테이블
$g5['visit_table'] = G5_TABLE_PREFIX.'visit'; // 방문자 테이블
$g5['visit_sum_table'] = G5_TABLE_PREFIX.'visit_sum'; // 방문자 합계 테이블
$g5['uniqid_table'] = G5_TABLE_PREFIX.'uniqid'; // 유니크한 값을 만드는 테이블
$g5['autosave_table'] = G5_TABLE_PREFIX.'autosave'; // 게시글 작성시 일정시간마다 글을 임시 저장하는 테이블
$g5['cert_history_table'] = G5_TABLE_PREFIX.'cert_history'; // 인증내역 테이블
$g5['qa_config_table'] = G5_TABLE_PREFIX.'qa_config'; // 1:1문의 설정테이블
$g5['qa_content_table'] = G5_TABLE_PREFIX.'qa_content'; // 1:1문의 테이블
$g5['content_table'] = G5_TABLE_PREFIX.'content'; // 내용(컨텐츠)정보 테이블
$g5['faq_table'] = G5_TABLE_PREFIX.'faq'; // 자주하시는 질문 테이블
$g5['faq_master_table'] = G5_TABLE_PREFIX.'faq_master'; // 자주하시는 질문 마스터 테이블
$g5['new_win_table'] = G5_TABLE_PREFIX.'new_win'; // 새창 테이블
$g5['menu_table'] = G5_TABLE_PREFIX.'menu'; // 메뉴관리 테이블
$g5['banner_table'] = G5_TABLE_PREFIX.'banner'; // 배너 테이블
$g5['intro_table'] = G5_TABLE_PREFIX.'intro'; // 인트로 테이블
$g5['character_table'] = G5_TABLE_PREFIX.'character'; // 캐릭터 테이블
$g5['class_table'] = G5_TABLE_PREFIX.'character_class'; // 캐릭터 클래스 테이블
$g5['side_table'] = G5_TABLE_PREFIX.'character_side'; // 캐릭터 소속 테이블
$g5['title_table'] = G5_TABLE_PREFIX.'character_title'; // 캐릭터 타이틀 테이블
$g5['title_has_table'] = G5_TABLE_PREFIX.'has_title'; // 캐릭터 보유 타이틀 테이블
$g5['couple_table'] = G5_TABLE_PREFIX.'couple'; // 커플관리 테이블
$g5['emoticon_table'] = G5_TABLE_PREFIX.'emoticon'; // 이모티콘 테이블
$g5['exp_table'] = G5_TABLE_PREFIX.'exp'; // 캐릭터 경험치 테이블
$g5['inventory_table'] = G5_TABLE_PREFIX.'inventory'; // 캐릭터 인벤토리 테이블
$g5['item_table'] = G5_TABLE_PREFIX.'item'; // 캐릭터 아이템 테이블
$g5['recepi_table'] = G5_TABLE_PREFIX.'item_recepi'; // 캐릭터 레시피 테이블
$g5['explorer_table'] = G5_TABLE_PREFIX.'item_explorer'; // 아이템 획득 
$g5['relation_table'] = G5_TABLE_PREFIX.'relation_character'; // 관계설정 테이블
$g5['order_table'] = G5_TABLE_PREFIX.'order'; // 주문관리 테이블
$g5['closthes_table'] = G5_TABLE_PREFIX.'character_closthes'; // 캐릭터 의상 테이블
$g5['call_table'] = G5_TABLE_PREFIX.'call_board'; // 호출 테이블
$g5['css_table'] = G5_TABLE_PREFIX.'css_config'; // CSS STYLE 정의 저장하는 테이블
$g5['article_table'] = G5_TABLE_PREFIX.'article'; // 프로필 항목 저장 테이블
$g5['article_default_table'] = G5_TABLE_PREFIX.'article_default'; // 프로필 기본 항목 설정값 테이블
$g5['value_table'] = G5_TABLE_PREFIX.'article_value'; // 프로필 항목 값 테이블
$g5['level_table'] = G5_TABLE_PREFIX.'level_setting'; // 레벨 업 셋팅 테이블
$g5['shop_table'] = G5_TABLE_PREFIX.'shop'; // 상점테이블
$g5['status_config_table'] = G5_TABLE_PREFIX.'status'; // 스탯 설정 테이블
$g5['status_table'] = G5_TABLE_PREFIX.'status_character'; // 스탯 보유 현황 테이블
$g5['backup_table'] = G5_TABLE_PREFIX.'backup'; // 백업 테이블
$g5['quest_table'] = G5_TABLE_PREFIX.'quest'; // 퀘스트 테이블
?>