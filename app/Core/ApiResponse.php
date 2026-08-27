<?php

namespace App\Core;

/**
 * Унифицированный формат JSON-ответов API.
 */
class ApiResponse
{
    private bool $success;
    private $data;
    private ?string $error;
    private int $httpCode = 200;

    private function __construct(bool $success, $data = null, ?string $error = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
    }

    /**
     * Создаёт успешный ответ.
     *
     * @param mixed $data
     * @param string $message (опционально)
     * @return self
     */
    public static function success($data = null, string $message = ''): self
    {
        $response = new self(true, $data, $message ?: null);
        return $response;
    }

    /**
     * Создаёт ответ с ошибкой.
     *
     * @param string $message
     * @param int $code HTTP-код по умолчанию 400
     * @return self
     */
    public static function error(string $message, int $code = 400): self
    {
        $response = new self(false, null, $message);
        $response->httpCode = $code;
        return $response;
    }

    /**
     * Устанавливает HTTP-код ответа.
     *
     * @param int $code
     * @return self
     */
    public function withHttpCode(int $code): self
    {
        $this->httpCode = $code;
        return $this;
    }

    /**
     * Отправляет JSON-ответ и завершает выполнение.
     *
     * @return void
     */
    public function send(): void
    {
        http_response_code($this->httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $this->success,
            'data'    => $this->data,
            'error'   => $this->error,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function getData()
    {
        return $this->data;
    }
}