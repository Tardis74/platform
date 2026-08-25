@echo off
setlocal enabledelayedexpansion

echo Создание структуры фронтенда в папке public...

:: Проверяем, существует ли public, и создаём при необходимости
if not exist "public" (
    mkdir "public"
)

cd public

:: Создание папок
mkdir assets\css 2>nul
mkdir assets\js 2>nul
mkdir assets\images 2>nul

mkdir views\layouts 2>nul
mkdir views\auth 2>nul
mkdir views\parent 2>nul
mkdir views\student 2>nul
mkdir views\teacher 2>nul
mkdir views\moderator 2>nul
mkdir views\admin 2>nul
mkdir views\educator 2>nul
mkdir views\canteen 2>nul
mkdir views\kpp 2>nul

:: Создание пустых файлов
type nul > index.php 2>nul
type nul > .htaccess 2>nul

type nul > assets\css\style.css 2>nul
type nul > assets\css\bootstrap.min.css 2>nul

type nul > assets\js\app.js 2>nul
type nul > assets\js\auth.js 2>nul
type nul > assets\js\components.js 2>nul

type nul > assets\images\logo.png 2>nul

type nul > views\layouts\main.php 2>nul

type nul > views\auth\login.php 2>nul
type nul > views\auth\register.php 2>nul

type nul > views\parent\dashboard.php 2>nul
type nul > views\student\dashboard.php 2>nul
type nul > views\teacher\dashboard.php 2>nul
type nul > views\moderator\dashboard.php 2>nul
type nul > views\admin\dashboard.php 2>nul
type nul > views\educator\dashboard.php 2>nul
type nul > views\canteen\dashboard.php 2>nul
type nul > views\kpp\dashboard.php 2>nul

echo.
echo Готово! Структура создана в %CD%
echo Вы можете заполнить файлы содержимым из предоставленных примеров.
pause
endlocal