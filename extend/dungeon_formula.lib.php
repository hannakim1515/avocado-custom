<?php
if (!defined('_GNUBOARD_')) exit;

/** Shared calculation only: no database writes, logs, turns or resource use. */
function dungeon_formula_result($ex, $total, $prev_value = 0, $last_value = 0, $buff_code_value = 0) {
	$result = array();

	$default_status = 0; // 기본 수치
	$is_critical = false; // 크리티컬 여뷰
	$critical_status = 0; // 크리티컬로 더해지는 추가 수치
	$total_status = 0; // 최종 수치

	// 연동코드 설정 가져오기

	// 기본 수치
	$default_status = rand($ex['ex_main_min'], $ex['ex_main_max']);
	$status = 0;
	if($ex['ex_is_main_status']) {
		// 스탯 연동을 사용할 경우
		$status = $total($ex['ex_main_status_type']);
		if($ex['ex_main_status_per']) {
			$status = $status * $ex['ex_main_status_per'];
		}
	}
	$default_status = (int)($default_status + $status + $prev_value);

	// 변동수치
	$cri_succed_per = $ex['ex_cri'];
	$cri_succed_per2 = 0;
	if($ex['ex_is_cri_status']) {
		// 스탯 연동을 사용할 경우
		$cri_succed_per2 = $total($ex['ex_cri_status_type']);
		if($ex['ex_cri_status_per']) {
			$cri_succed_per2 = $cri_succed_per2 * $ex['ex_cri_status_per'];
		}
	}
	$cri_succed_per = $cri_succed_per + $cri_succed_per2;

	// 변동 수치의 퍼센트가 0 보다 클때
	if($cri_succed_per > 0) {
		// 크리티컬 판정 여부 확인
		$cri_succed_seed = rand(0, 100);
		if($cri_succed_seed <= $cri_succed_per) {
			// 크리티컬 성공
			$is_critical = true;

			// 크리티컬 성공할 경우 추가적으로 더해지는 수치를 계산한다.
			// 이 경우에는 기본수치에 기반한 비율이 더해지므로, 비율 수치를 계산한다.
			$add_status_per = $ex['ex_cri_add_per'];
			$add_status_per2 = 0;
			if($ex['ex_is_cri_add_status']) {
				// 스탯 연동을 사용할 경우
				$add_status_per2 = $total($ex['ex_cri_add_status_type']);
				if($ex['ex_cri_add_status_per']) {
					$add_status_per2 = $add_status_per2 * $ex['ex_cri_add_status_per'];
				}
			}
			$temp_check_value = $add_status_per + $add_status_per2;
			$critical_status = $temp_check_value > 0 ? (int)($default_status * ($temp_check_value/100)) : 0;
		}
	}

	$total_status = $default_status + $critical_status;
	if($ex['ex_all_per']) {
		$total_status = (int)($total_status * $ex['ex_all_per']);
	}

	// 버프 코드 적용
	$total_status = $total_status + $buff_code_value;

	$total_status = $total_status + $last_value;

	$result['default'] = $default_status;
	$result['is_cri'] = $is_critical;
	$result['cri_value'] = $critical_status;
	$result['value'] = $total_status;

	return $result;
}
