<?php

const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024;

function upload_directory() {
    return rtrim(getenv('UPLOAD_DIR') ?: '/var/www/uploads', DIRECTORY_SEPARATOR);
}

function uploaded_files($field) {
    if (!isset($_FILES[$field])) {
        return [];
    }

    $upload = $_FILES[$field];
    if (!is_array($upload['name'])) {
        return [$upload];
    }

    $files = [];
    foreach ($upload['name'] as $index => $name) {
        $files[] = [
            'name' => $name,
            'tmp_name' => $upload['tmp_name'][$index] ?? '',
            'error' => $upload['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $upload['size'][$index] ?? 0,
        ];
    }

    return $files;
}

function upload_error_message($error) {
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return '첨부파일은 개당 10MB 이하만 업로드할 수 있습니다.';
    }

    if ($error === UPLOAD_ERR_PARTIAL) {
        return '첨부파일이 완전히 업로드되지 않았습니다.';
    }

    return '첨부파일 업로드에 실패했습니다.';
}

function normalized_file_name($name) {
    $name = basename(str_replace('\\', '/', trim((string)$name)));
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

    while (strlen($name) > 255) {
        $name = substr($name, 0, -1);
    }
    while ($name !== '' && !preg_match('//u', $name)) {
        $name = substr($name, 0, -1);
    }

    return $name;
}

function save_attachments($conn, $postId, $field = 'attachments') {
    $directory = upload_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('첨부파일 저장 폴더를 만들 수 없습니다.');
    }

    $savedPaths = [];

    try {
        foreach (uploaded_files($field) as $file) {
            $error = (int)$file['error'];
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException(upload_error_message($error));
            }

            $size = (int)$file['size'];
            if ($size <= 0 || $size > MAX_ATTACHMENT_SIZE) {
                throw new RuntimeException('첨부파일은 비어 있지 않은 10MB 이하 파일만 업로드할 수 있습니다.');
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('유효하지 않은 업로드 파일입니다.');
            }

            $originalName = normalized_file_name($file['name']);
            if ($originalName === '' || $originalName === '.' || $originalName === '..') {
                throw new RuntimeException('파일 이름이 올바르지 않습니다.');
            }

            $storedPath = $directory . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16));
            if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
                throw new RuntimeException('첨부파일을 저장하지 못했습니다.');
            }
            chmod($storedPath, 0640);
            $savedPaths[] = $storedPath;

            $stmt = $conn->prepare(
                'INSERT INTO attachments (post_id, original_name, stored_path, size_bytes) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('issi', $postId, $originalName, $storedPath, $size);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $exception) {
        remove_attachment_files($savedPaths);
        throw $exception;
    }

    return $savedPaths;
}

function load_post_attachments($conn, $postId) {
    $stmt = $conn->prepare(
        'SELECT id, original_name, stored_path, size_bytes FROM attachments WHERE post_id = ? ORDER BY id ASC'
    );
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function is_upload_path($path) {
    $directory = realpath(upload_directory());
    $file = realpath($path);

    return $directory !== false && $file !== false && str_starts_with($file, $directory . DIRECTORY_SEPARATOR);
}

function remove_attachment_files($paths) {
    foreach ($paths as $path) {
        if (is_string($path) && is_upload_path($path) && is_file($path) && !unlink($path)) {
            error_log('Failed to remove attachment: ' . $path);
        }
    }
}
