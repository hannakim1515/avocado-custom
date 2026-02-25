$(function() {
	
	if($('.map-img-viewer .anker .on').length > 0) { 
		c_x = $('.map-img-viewer .anker .on').offset().left;
		c_y = $('.map-img-viewer .anker .on').offset().top;
	}

	now_map_size(zoom);
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

function map_search (idx) {
	$('.map-simple-info .control').hide();
	var formData = new FormData();
	formData.append("ma_id", idx);
	$.ajax({
		url:g5_url + '/map/proc/map_search.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(data) {
				$('.map-searchPopup .pannel').empty().append(data);
				$('.map-searchPopup').addClass('active');
				$('.map_wrap').addClass('set-mask');
			}
			reset_search_count();
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
	$('.map-searchPopup').removeClass('active');
	$('.map-searchPopup .pannel').empty();
	$('.map_wrap').removeClass('set-mask');
}






