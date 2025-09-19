@echo off
echo Building Tailwind CSS for production...
echo.

REM Check if node_modules exists
if not exist "node_modules" (
    echo Installing dependencies...
    npm install
    echo.
)

REM Build CSS
echo Building CSS...
npx tailwindcss -i ./src/input.css -o ./dist/output.css --minify

if %errorlevel% equ 0 (
    echo.
    echo ✅ CSS build completed successfully!
    echo Output: dist/output.css
) else (
    echo.
    echo ❌ CSS build failed!
    echo Please check the error messages above.
)

echo.
pause
