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

function all_admin_accounts(PDO $pdo) {
    try {
        $stmt = $pdo->query(
            'SELECT a.id, a.name, a.email, a.committee_id, a.is_active, a.created_at,
                    c.name AS committee_name, c.role AS committee_role
             FROM admins a
             LEFT JOIN committee c ON c.id = a.committee_id
             ORDER BY a.is_active DESC, a.created_at DESC'
        );
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

