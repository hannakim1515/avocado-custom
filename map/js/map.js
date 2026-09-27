$(function() {
	
	if($('.map-img-viewer .anker .on').length > 0) { 
		c_x = $('.map-img-viewer .anker .on').offset().left;
		c_y = $('.map-img-viewer .anker .on').offset().top;
	}

	now_map_size(zoom);
	if($('.map-vn-wrap').length && typeof current_ma_id != 'undefined') {
		map_show_location_actions(current_ma_id);
	}
});

$(window).on('resize', function() {
	now_map_size(zoom);
});

$('.map-img-viewer').on('mousedown', function (e) {
	dragFlag = true;
	var obj = $(this);
	x = obj.scrollLeft();
	y = obj.scrollTop();
	pre_x = e.screenX;
	pre_y = e.screenY;					
	$(this).css("cursor", "move");
});
$('.map-img-viewer').on('mousemove', function (e) {
	if (dragFlag) {
		var obj = $(this);
		obj.scrollLeft(x - e.screenX + pre_x);
		obj.scrollTop(y - e.screenY + pre_y);
		return false;
	}
});

$('.map-img-viewer').on('mouseup', function (e) {
	dragFlag = false;
	$(this).css("cursor", "default");
});
$('body').on('mouseup', function (e) {
	dragFlag = false;
	$(this).css("cursor", "default");
});

function now_map_size(zoom=1) {
	var m_w, m_h;
	var w_width = $(window).outerWidth();
	var w_height = $(window).outerHeight();

	if(w_width > w_height) {
		// 가로가 더 긴 형태
		// 가로 사이즈 기준으로 크기를 잡는다.
		m_w = w_width;
		m_h = m_w * raito;

		if(m_h < w_height) {
			// 세로 사이즈가 화면보다 작아질 경우
			// 세로사이즈 기준으로 잡아 버리자.
			m_h = w_height;
			m_w = m_h / raito;
		}
	} else {
		// 세로가 더 긴 형태
		m_h = w_height;
		m_w = m_h / raito;

		if(m_w < w_width) {
			// 가로 사이즈가 화면보다 작아질 경우
			// 가로사이즈 기준으로 잡아 버리자.
			m_w = w_width;
			m_h = m_w * raito;
		}
	}

	z_m_w = m_w * zoom;
	z_m_h = m_h * zoom;	

	if(z_m_w >= w_width && z_m_h >= w_height) { 
		m_w = z_m_w;
		m_h = z_m_h;	
	}

	if(pre_w == 0) pre_w = m_w;
	if(pre_h == 0) pre_h = m_h;

	$('.map-img-viewer > .bak').css('width', m_w).css('height', m_h);
	var n_x, n_y;

	if(m_w - pre_w >= 0) { 
		n_x = $('.map-img-viewer').scrollLeft() + (m_w - pre_w) - (w_width/2);
		n_y = $('.map-img-viewer').scrollTop() + (m_h - pre_h) - (w_height/2);
	} else {
		n_x = $('.map-img-viewer').scrollLeft() + (m_w - pre_w) + (w_width/2);
		n_y = $('.map-img-viewer').scrollTop() + (m_h - pre_h) + (w_height/2);
	}

	$('.map-img-viewer').scrollLeft(n_x);
	$('.map-img-viewer').scrollTop(n_y);

	pre_w = m_w;
	pre_h = m_h;
}


function open_map_pannel(idx) {
	if($('.map-vn-wrap').length) {
		map_show_location_actions(idx);
		return;
	}

	var formData = new FormData();
	formData.append("ma_id", idx);
	$.ajax({
		url:g5_url + '/map/map_detail.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(data) {
				$('.map-descript-box').empty().append(data);
				$('.map-img-viewer .anker a').removeClass('active');
				$('.map-img-viewer .anker a[data-idx="'+idx+'"]').addClass('active');

				if($('.map-descript-box').find('.type-gate').length > 0) {
					$('.map-descript-flip-box').addClass('type-gate-box');
				} else {
					$('.map-descript-flip-box').removeClass('type-gate-box');
				}
			}
		}
		, error: function(data, status, err) {
			$('.map-descript-box').empty();
			$('.map-img-viewer .anker a').removeClass('active');
		}
		, complete: function() { 
			// Complete
		}
	});

}

var mapCountdownTimer = null;
var mapVnMovingTo = 0;
var mapAfterRewardCallback = null;

function map_format_seconds_js(seconds) {
	seconds = parseInt(seconds, 10);
	if(isNaN(seconds) || seconds <= 0) return '00:00:00';
	var h = Math.floor(seconds / 3600);
	var m = Math.floor((seconds % 3600) / 60);
	var s = seconds % 60;
	return ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2) + ':' + ('0' + s).slice(-2);
}

function map_init_countdowns() {
	if(mapCountdownTimer) {
		clearInterval(mapCountdownTimer);
		mapCountdownTimer = null;
	}

	var $items = $('.map-countdown');
	if(!$items.length) return;

	$items.each(function() {
		var remaining = parseInt($(this).attr('data-remaining'), 10);
		if(isNaN(remaining)) remaining = 0;
		$(this).data('end-time', Date.now() + (remaining * 1000));
	});

	function tick() {
		var hasRunning = false;
		$('.map-countdown').each(function() {
			var $count = $(this);
			var endTime = parseInt($count.data('end-time'), 10);
			var remaining = Math.ceil((endTime - Date.now()) / 1000);

			if(remaining <= 0) {
				$count.find('em').text('00:00:00');
				$count.hide();
				$count.closest('.map-work-state').removeClass('working').addClass('complete').find('strong').first().text('작업 완료');
				$count.siblings('.map-countdown-complete').show();
			} else {
				hasRunning = true;
				$count.find('em').text(map_format_seconds_js(remaining));
			}
		});

		if(!hasRunning && mapCountdownTimer) {
			clearInterval(mapCountdownTimer);
			mapCountdownTimer = null;
		}
	}

	tick();
	mapCountdownTimer = setInterval(tick, 1000);
}

function map_show_location_actions(idx) {
	var formData = new FormData();
	formData.append("ma_id", idx);
	$.ajax({
		url:g5_url + '/map/map_actions.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(mapVnMovingTo && idx != current_ma_id) return;
			if(data) {
				var $box = $('.map-vn-action-box');
				$box.empty().append(data).show().removeClass('is-open');
				if($box.length) $box[0].offsetHeight;
				$box.addClass('is-open');
				$('.map-img-viewer .anker a').removeClass('active');
				$('.map-img-viewer .anker a[data-idx="'+idx+'"]').addClass('active');
				map_init_countdowns();
			}
		}
		, error: function() {
			$('.map-vn-action-box').empty().hide();
		}
	});
}

function map_set_scene_npc(src, name) {
	var $npc = $('.map-vn-character');
	if(!$npc.length) return;

	$npc.empty();
	var cast = [];
	if(Array.isArray(src)) {
		cast = src;
	} else if(src && typeof src === 'object') {
		cast = [src];
	} else if(src) {
		cast = [{img: src, name: name || '', position: 'center'}];
	}

	if(!cast.length) {
		$npc.removeClass('active');
		return;
	}
	var uniqueCast = [];
	var seenCast = {};
	$.each(cast, function(_, actor) {
		if(!actor) return;
		var key = actor.id ? 'id:' + actor.id : 'name:' + (actor.name || actor.img || '');
		if(seenCast[key]) return;
		seenCast[key] = true;
		uniqueCast.push(actor);
	});
	cast = uniqueCast;

	$.each(cast, function(index, actor) {
		var img = actor && (actor.img || actor.src) ? (actor.img || actor.src) : '';
		var label = actor && actor.name ? actor.name : (name || '');
		if(!img) return;
		var $item = $('<div>', {
			'class': 'map-vn-character-item is-entering ' + (actor.position || (index === 0 ? 'center' : (index % 2 ? 'left' : 'right'))),
			'data-actor-id': actor && actor.id ? actor.id : '',
			'data-actor-name': label || ''
		});
		$item.append($('<img>', {src: img, alt: label || ''}));
		$npc.append($item);
		setTimeout(function() { $item.removeClass('is-entering'); }, 20);
	});

	if(!$npc.find('img').length) {
		$npc.removeClass('active');
		return;
	}
	$npc.addClass('active');
}

var mapVnDialog = {
	maId: 0,
	name: '',
	lines: [],
	index: 0,
	callback: null,
	keepStanding: false
};

function map_vn_find_actor(reference) {
	var ref = $.trim(reference || '');
	var idMatch = ref.match(/^(\d+)\s*\|/);
	var name = idMatch ? $.trim(ref.replace(/^\d+\s*\|\s*/, '')) : ref;
	var actor = null;
	$.each(mapVnDialog.cast || [], function(_, item) {
		var itemId = item && (item.id || item.ch_id) ? String(item.id || item.ch_id) : '';
		if((idMatch && itemId == idMatch[1]) || (!idMatch && item.name == name)) { actor = item; return false; }
	});
	return actor;
}

function map_vn_find_actor_by_name(name) {
	var actor = null;
	name = $.trim(name || '');
	if(!name) return null;
	$.each(mapVnDialog.cast || [], function(_, item) {
		if(item && $.trim(item.name || '') == name) { actor = item; return false; }
	});
	return actor;
}

function map_vn_set_active_actor(actor, hideAll) {
	var $area = $('.map-vn-character');
	if(hideAll) { $area.removeClass('active').addClass('is-hidden'); return; }
	$area.removeClass('is-hidden');
	var actorKey = actor && (actor.id || actor.ch_id) ? String(actor.id || actor.ch_id) : '';
	if(actor && actor.img && !(actorKey ? $area.find('.map-vn-character-item[data-actor-id="' + actorKey + '"]').length : $area.find('.map-vn-character-item[data-actor-name="' + actor.name + '"]').length)) {
		var visible = mapVnDialog.visibleCast || [];
		var isVisible = $.grep(visible, function(item) {
			return actorKey ? String(item.id || item.ch_id || '') == actorKey : String(item.name || '') == String(actor.name || '');
		}).length > 0;
		map_set_scene_npc(isVisible ? visible : visible.concat([actor]));
	}
	var actorId = actorKey;
	var actorName = actor && actor.name ? String(actor.name) : '';
	$area.find('.map-vn-character-item').removeClass('is-speaking').each(function() {
		if((actorId && String($(this).data('actor-id')) == actorId) || (!actorId && actorName && String($(this).data('actor-name')) == actorName)) $(this).addClass('is-speaking');
	});
	if($area.find('img').length) $area.addClass('active');
}

function map_vn_exit_actor(reference) {
	var actor = map_vn_find_actor(reference);
	if(!actor) return;
	var $item = $('.map-vn-character-item[data-actor-id="' + actor.id + '"]');
	$item.addClass('is-leaving').removeClass('is-speaking');
	setTimeout(function() {
		$item.remove();
		if(!$('.map-vn-character-item').length) $('.map-vn-character').removeClass('active');
	}, 230);
	mapVnDialog.visibleCast = $.grep(mapVnDialog.visibleCast || [], function(item) { return String(item.id) != String(actor.id); });
}

function map_vn_parse_dialog_line(line) {
	var text = $.trim(line);
	var type = 'dialogue';
	var speaker = '';
	var exitMatch = text.match(/\s*\[NPC\s*:\s*([^\]]+?)\s+퇴장\]\s*$/i);
	var exitReference = exitMatch ? $.trim(exitMatch[1]) : '';
	if(exitMatch) text = $.trim(text.substring(0, exitMatch.index));
	var match = text.match(/^\[(대사|dialogue|나레이션|narration)\]\s*/i);

	if(match) {
		var label = match[1].toLowerCase();
		type = (label == '나레이션' || label == 'narration') ? 'narration' : 'dialogue';
		text = $.trim(text.substring(match[0].length));
	} else {
		var npcMatch = text.match(/^\[NPC\s*:\s*([^\]]+)\]\s*/i);
		if(npcMatch) {
			var npcReference = $.trim(npcMatch[1]);
			var npcIdMatch = npcReference.match(/^\s*\d+\s*\|\s*(.+?)\s*$/);
			speaker = npcIdMatch ? $.trim(npcIdMatch[1]) : npcReference;
			text = $.trim(text.substring(npcMatch[0].length));
			return {type: type, text: text, speaker: speaker, actorReference: npcReference, exitReference: exitReference};
		}
	}

	return {
		type: type,
		text: text,
		speaker: speaker,
		actorReference: '',
		exitReference: exitReference
	};
}

function map_vn_apply_dialog_line(line) {
	var $dialog = $('.map-vn-dialog');
	var type = line && line.type == 'narration' ? 'narration' : 'dialogue';
	var text = line ? line.text : '';
	var speaker = line && line.speaker ? line.speaker : mapVnDialog.name;
	if(line && line.exitReference) map_vn_exit_actor(line.exitReference);

	$dialog.removeClass('is-dialogue is-narration').addClass('is-' + type);
	if(type == 'narration') {
		// 첫 나레이션은 빈 장면으로 시작하되, 이미 등장한 NPC는 나레이션 중에도 유지한다.
		if($('.map-vn-character-item').length) map_vn_set_active_actor(null, false);
		else map_vn_set_active_actor(null, true);
		$('.map-vn-dialog-name').hide().text('');
	} else {
		var actor = line && line.actorReference ? map_vn_find_actor(line.actorReference) : mapVnDialog.primary;
		// ID가 없는 기존 [NPC:이름] 문법이나 캐스트 ID 누락 데이터에서도 실제 화자를 우선한다.
		if(!actor && line && line.speaker) actor = map_vn_find_actor_by_name(line.speaker);
		if(actor && actor.img) {
			var visible = mapVnDialog.visibleCast || [];
			var actorKey = String(actor.id || actor.ch_id || '');
			if(!$.grep(visible, function(item) {
				return actorKey ? String(item.id || item.ch_id || '') == actorKey : String(item.name || '') == String(actor.name || '');
			}).length) visible.push(actor);
			mapVnDialog.visibleCast = visible;
		}
		map_vn_set_active_actor(actor, false);
		$('.map-vn-dialog-name').show().text(speaker || mapVnDialog.name || '');
	}
	$('.map-vn-dialog-text').removeClass('is-dialogue is-narration').addClass('is-' + type).text(text);
}

function map_vn_choice(idx) {
	open_map_pannel(idx);
}

function map_vn_move_choice(idx) {
	if(idx == current_ma_id) {
		open_map_pannel(idx);
		return;
	}
	mapVnMovingTo = idx;
	$('.map-vn-action-box').empty().hide();
	map_move(idx);
}

function map_vn_set_current(maId, maName) {
	var $buttons = $('.map-vn-choice-scroll button[data-map-id]');
	$buttons.removeClass('current');
	$buttons.each(function() {
		var name = $(this).attr('data-map-name') || $(this).find('strong').text();
		$(this).find('strong').text(name + '로 이동');
		$(this).find('span').text('이동');
	});

	var $current = $('.map-vn-choice-scroll button[data-map-id="'+maId+'"]');
	if($current.length) {
		$current.addClass('current');
		$current.find('strong').text(maName);
		$current.find('span').text('현재 위치');
	}

	$('.map-vn-place span').text('현재 위치: ' + maName);
	current_ma_id = maId;
}

function map_clear_search_popup() {
	$('.map-searchPopup').removeClass('active');
	$('.map-searchPopup .pannel').empty();
	if(!$('.map-vn-reward-layer.active:visible').length) {
		$('.map_wrap').removeClass('set-mask');
	}
}

function map_vn_clear_rewards() {
	$('.map-vn-reward-layer').removeClass('active').hide();
	$('.map-vn-reward-list').empty();
	mapAfterRewardCallback = null;
	if(!$('.map-searchPopup.active').length) {
		$('.map_wrap').removeClass('set-mask');
	}
}

function map_vn_close_rewards() {
	var callback = mapAfterRewardCallback;
	$('.map-vn-reward-layer').removeClass('active').hide();
	$('.map-vn-reward-list').empty();
	mapAfterRewardCallback = null;
	if(!$('.map-searchPopup.active').length) {
		$('.map_wrap').removeClass('set-mask');
	}
	if(typeof callback == 'function') callback();
}

function map_vn_set_background(src) {
	var $scene = $('.map-vn-scene');
	if(!$scene.length) return;

	src = $.trim(String(src || ''));
	if(!src) {
		$scene.css('background-image', 'none');
		return;
	}

	var safeSrc = src.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/[\r\n]/g, '');
	$scene.css('background-image', 'url("' + safeSrc + '")');
}

function map_vn_after_move(maId, maName, npcSrc, npcName, content, backgroundSrc) {
	$('.map-descript-box').empty();
	map_clear_search_popup();
	$('.map-vn-action-box').empty().hide();
	map_vn_set_background(backgroundSrc);
	map_set_scene_npc(npcSrc, npcName || maName);
	map_vn_set_current(maId, maName);
	mapVnMovingTo = 0;
	map_vn_start_dialog(maId, npcName || maName, content, function() {
		map_show_location_actions(maId);
	});
}

function map_vn_start_dialog(maId, name, content, callback, cast, primary, keepStanding) {
	var lines = [];
	if(content) {
		lines = String(content).replace(/\r/g, '').split('\n');
		lines = $.grep(lines, function(line) {
			return $.trim(line) !== '';
		});
		lines = $.map(lines, function(line) {
			return map_vn_parse_dialog_line(line);
		});
	}

	mapVnDialog.maId = maId;
	mapVnDialog.name = name || '';
	mapVnDialog.lines = lines;
	mapVnDialog.index = 0;
	mapVnDialog.callback = callback || null;
	mapVnDialog.cast = cast || [];
	mapVnDialog.primary = primary || (mapVnDialog.cast.length ? mapVnDialog.cast[0] : null);
	mapVnDialog.visibleCast = mapVnDialog.primary ? [mapVnDialog.primary] : [];
	mapVnDialog.keepStanding = !!keepStanding;

	if(!lines.length) {
		map_vn_end_dialog();
		return;
	}

	$('.map-vn-scene').addClass('dialog-on');
	map_vn_apply_dialog_line(lines[0]);
	if(!lines[0].action) $('.map-vn-dialog').show();
}

function map_vn_next_dialog() {
	if(!mapVnDialog.lines.length) return;

	mapVnDialog.index++;
	if(mapVnDialog.index >= mapVnDialog.lines.length) {
		map_vn_end_dialog();
		return;
	}

	map_vn_apply_dialog_line(mapVnDialog.lines[mapVnDialog.index]);
}

function map_vn_end_dialog() {
	var callback = mapVnDialog.callback;
	mapVnDialog.callback = null;
	$('.map-vn-dialog').hide().removeClass('is-dialogue is-narration');
	$('.map-vn-dialog-text').removeClass('is-dialogue is-narration');
	$('.map-vn-scene').removeClass('dialog-on');
	if(!mapVnDialog.keepStanding) $('.map-vn-character').empty().removeClass('active is-hidden');
	else $('.map-vn-character-item').removeClass('is-speaking');
	if(typeof callback == 'function') callback();
}

$(document).on('click', '.map-vn-dialog', function() {
	map_vn_next_dialog();
});

$(document).on('click', '.map-vn-reward-layer', function(e) {
	if(e.target === this) map_vn_close_rewards();
});

function map_move(idx) {
	var formData = new FormData();
	formData.append("idx", idx);
	$.ajax({
		url:g5_url + '/map/proc/map_move.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(data) {
				$('.map-script').empty().append(data);
			}
		}
	});
}

function map_handle_vn_response(data) {
	var marker = '<!--MAP_VN_JSON-->';
	if(typeof data != 'string' || data.indexOf(marker) !== 0) return false;

	var payload = {};
	try {
		payload = JSON.parse(data.substring(marker.length));
	} catch(e) {
		return false;
	}

	map_run_vn_payload(payload);
	return true;
}

function map_run_vn_payload(payload) {
	if(!payload) return;

	$('.map-vn-action-box').empty().hide();
	map_clear_search_popup();
	map_vn_clear_rewards();

	if(payload.npc_cast && payload.npc_cast.length) {
		// 대사 첫 줄에서만 메인 NPC를 세운다. 나레이션 시작 시에는 스탠딩을 비운다.
		map_set_scene_npc('', '');
	} else if(payload.npc_img || payload.npc_name) {
		map_set_scene_npc(payload.npc_img || '', payload.npc_name || '');
	}

	var maId = payload.ma_id || current_ma_id;
	var speaker = payload.speaker || payload.npc_name || payload.title || '조사';
	var content = payload.content || '';

	map_vn_start_dialog(maId, speaker, content, function() {
		if(payload.choices && payload.choices.length) {
			map_vn_show_event_choices(payload);
		} else {
			if(payload.rewards && payload.rewards.length) {
				map_vn_show_rewards(payload.rewards, function() {
					map_show_location_actions(maId);
				});
			} else {
				map_show_location_actions(maId);
			}
		}
	}, payload.npc_cast || [], (payload.npc_cast && payload.npc_cast.length) ? payload.npc_cast[0] : null, !!(payload.choices && payload.choices.length));
}

function map_vn_show_rewards(rewards, callback) {
	if(!rewards || !rewards.length) {
		if(typeof callback == 'function') callback();
		return;
	}
	var $layer = $('.map-vn-reward-layer');
	if(!$layer.length) {
		if(typeof callback == 'function') callback();
		return;
	}

	mapAfterRewardCallback = callback || null;
	$('.map-vn-action-box').empty().hide();
	map_clear_search_popup();
	$layer.removeClass('active');
	var $list = $layer.find('.map-vn-reward-list');
	$list.empty();

	$.each(rewards, function(_, reward) {
		var type = reward.type || 'basic';
		var $item = $('<div>', {'class': 'map-vn-reward-item map-vn-reward-' + type});
		if(reward.img) {
			var $thumb = $('<div>', {'class': 'map-vn-reward-thumb'});
			$thumb.append($('<img>', {'src': reward.img, 'alt': ''}).on('error', function() {
				$(this).remove();
			}));
			$item.append($thumb);
		}

		var $desc = $('<div>', {'class': 'map-vn-reward-desc'});
		$desc.append($('<strong>').text(reward.title || '보상 획득'));
		if(reward.desc) $desc.append($('<span>').text(reward.desc));
		$item.append($desc);
		$list.append($item);
	});

	$layer.css('display', 'flex').addClass('active');
	$('.map_wrap').addClass('set-mask');
}

function map_vn_show_event_choices(payload) {
	var $box = $('.map-vn-action-box');
	var $wrap = $('<div>', {'class': 'map-pannel map-vn-pannel map-npc-choice-scene'});
	var $actions = $('<div>', {'class': 'map-actions map-actions-vn'});

	$.each(payload.choices, function(_, choice) {
		var $button = $('<button>', {'type': 'button', 'class': 'map-vn-action-choice'});
		$button.append($('<span>').text(choice.text || '선택'));
		$button.on('click', function() {
			map_npc_choice(payload.encounter_id, choice.choice_id);
		});
		$actions.append($button);
	});

	$wrap.append($actions);
	$box.empty().append($wrap).show().removeClass('is-open');
	if($box.length) $box[0].offsetHeight;
	$box.addClass('is-open');
}

function map_npc_choice(encounter_id, choice_id) {
	var formData = new FormData();
	formData.append("encounter_id", encounter_id);
	formData.append("choice_id", choice_id);
	$.ajax({
		url:g5_url + '/map/proc/map_npc_choice.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(!map_handle_vn_response(data)) {
				map_action_popup(data);
			}
		}
		, error: function() {
			map_action_popup('<div class="descript"><div class="tbl"><div class="cell"><div class="txt error">처리 중 오류가 발생했습니다.</div></div></div></div>');
		}
	});
}

function map_search (idx) {
	$('.map-simple-info .control').hide();
	var formData = new FormData();
	formData.append("ma_id", idx);
	formData.append("vn", $('.map-vn-wrap').length ? 1 : 0);
	$.ajax({
		url:g5_url + '/map/proc/map_search.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(map_handle_vn_response(data)) {
				return;
			}
			if(data) {
				$('.map-searchPopup .pannel').empty().append(data);
				$('.map-searchPopup').addClass('active');
				$('.map_wrap').addClass('set-mask');
			}
		}
		, error: function(data, status, err) {
			$('.map-searchPopup').removeClass('active');
			$('.map-searchPopup .pannel').empty();
		}
		, complete: function() {
			$('.map-simple-info .control').show();
			reset_search_count();
		}
	});
}

function reset_search_count() {
	$.ajax({
		url:g5_url + '/map/proc/map_search_count.php'
		, processData: false
		, contentType: false
		, success: function(data){
			$('[data-search-counter]').text(data);
		}
		,error: function(data, status, err) {
			$('[data-search-counter]').text(0);
		}
	});
}

function map_search_close() {
	map_clear_search_popup();
}

function map_action_popup(data) {
	mapAfterRewardCallback = null;
	map_vn_clear_rewards();
	if(map_handle_vn_response(data)) return;
	if(data) {
		$('.map-searchPopup .pannel').empty().append(data);
		$('.map-searchPopup').addClass('active');
		$('.map_wrap').addClass('set-mask');
	}
}

function map_refresh_current_pannel() {
	var idx = $('.map-img-viewer .anker a.on').data('idx');
	if(idx) open_map_pannel(idx);
}

function map_action_start(action_id) {
	var formData = new FormData();
	formData.append("action_id", action_id);
	$.ajax({
		url:g5_url + '/map/proc/map_action_start.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			map_action_popup(data);
			map_refresh_current_pannel();
		}
		, error: function(data, status, err) {
			map_action_popup('<div class="descript"><div class="tbl"><div class="cell"><div class="txt error">처리 중 오류가 발생했습니다.</div></div></div></div>');
		}
	});
}

function map_work_complete(work_id) {
	var formData = new FormData();
	formData.append("work_id", work_id);
	$.ajax({
		url:g5_url + '/map/proc/map_work_complete.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			map_action_popup(data);
			map_refresh_current_pannel();
		}
		, error: function(data, status, err) {
			map_action_popup('<div class="descript"><div class="tbl"><div class="cell"><div class="txt error">처리 중 오류가 발생했습니다.</div></div></div></div>');
		}
	});
}
