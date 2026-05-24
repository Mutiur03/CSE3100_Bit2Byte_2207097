<?php

function all_events(PDO $pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM events ORDER BY event_date DESC');
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (is_missing_table_error($e)) {
            return [];
        }
        throw $e;
    }
}

function all_projects(PDO $pdo) {
    try{
        $stmt = $pdo->query('SELECT * FROM projects ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (is_missing_table_error($e)) {
            return [];
        }
        throw $e;
    }
}

function all_committee_members(PDO $pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM committee ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (is_missing_table_error($e)) {
            return [];
        }
        throw $e;
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

