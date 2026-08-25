<?php
namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\CanteenSeating;
use App\Models\CanteenAttendance;
use App\Models\CanteenSpecialMeal;
use RuntimeException;

class CanteenController extends BaseController
{
    /**
     * Получить итоговую рассадку на сегодня с учётом отметок.
     */
    public function seatingGetToday(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, ['canteen', 'admin']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $date = $payload['date'] ?? date('Y-m-d');
        if (!strtotime($date)) {
            return ApiResponse::error('Invalid date format.', 400);
        }

        $data = CanteenSeating::getTodayWithAttendance($date);
        return ApiResponse::success($data);
    }

    /**
     * Выгрузка рассадки в CSV.
     */
    public function seatingExport(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, ['canteen', 'admin']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $date = $payload['date'] ?? date('Y-m-d');
        $format = $payload['format'] ?? 'csv';

        if ($format !== 'csv' && $format !== 'json') {
            return ApiResponse::error('Unsupported format. Use csv or json.', 400);
        }

        $data = CanteenSeating::getAllForExport($date);

        if ($format === 'json') {
            return ApiResponse::success($data);
        }

        // CSV
        $filename = "seating_{$date}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['table_number', 'seat_number', 'student_name', 'class_name', 'is_present']);
        foreach ($data as $row) {
            fputcsv($output, [
                $row['table_number'],
                $row['seat_number'],
                $row['student_name'],
                $row['class_name'],
                $row['is_present'] ? 'Да' : 'Нет'
            ]);
        }
        fclose($output);

        file_put_contents(
            __DIR__ . '/../../storage/logs/canteen.log',
            date('Y-m-d H:i:s') . " [user_id: {$this->getCurrentUser($token)['id']}] Выгрузка CSV рассадки на $date\n",
            FILE_APPEND
        );
        exit;
    }

    /**
     * Статистика по питанию.
     */
    public function statsGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, ['canteen', 'admin']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $date = $payload['date'] ?? date('Y-m-d');
        if (!strtotime($date)) {
            return ApiResponse::error('Invalid date format.', 400);
        }

        $presentCount = CanteenAttendance::countPresentOnDate($date);

        // Общее количество учеников (активных)
        $totalStudents = (int)$db->fetch("SELECT COUNT(*) as cnt FROM students WHERE status = 'active'")['cnt'];

        // Особые графики
        $specialMeals = CanteenSpecialMeal::getAll();

        return ApiResponse::success([
            'date'            => $date,
            'present_count'   => $presentCount,
            'total_students'  => $totalStudents,
            'special_meals'   => $specialMeals,
        ]);
    }
}