<?php

namespace App\Models;

use App\Core\DB;

class ParentStudent
{
    public static function activateByStudent(int $studentId): void
    {
        $db = DB::getInstance();
        $parents = $db->fetchAll(
            "SELECT parent_id FROM link_requests 
             WHERE student_id = :student_id AND status = 'approved'",
            ['student_id' => $studentId]
        );

        foreach ($parents as $p) {
            $db->query(
                "INSERT IGNORE INTO parent_student (parent_id, student_id) VALUES (:parent_id, :student_id)",
                ['parent_id' => $p['parent_id'], 'student_id' => $studentId]
            );
        }
    }
}