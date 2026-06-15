<?php

// 게시판 종류를 한 곳에서 관리하고 잘못된 게시판 값은 기본 게시판으로 보정합니다.

const BOARD_TYPES = [
    'general' => '일반 게시판',
    'free' => '자유 게시판',
];

function board_type($value) {
    return is_string($value) && isset(BOARD_TYPES[$value]) ? $value : 'general';
}

function board_label($type) {
    $type = board_type($type);
    return BOARD_TYPES[$type];
}

function board_url($type, $params = []) {
    $params = array_merge(['board' => board_type($type)], $params);
    return 'index.php?' . http_build_query($params);
}
