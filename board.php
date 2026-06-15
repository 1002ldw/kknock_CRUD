<?php

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
