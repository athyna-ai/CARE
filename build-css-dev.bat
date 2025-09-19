@echo off
echo Building Tailwind CSS for development...
echo.

REM Check if node_modules exists
if not exist "node_modules" (
    echo Installing dependencies...
    npm install
    echo.
)

REM Build CSS with watch mode
echo Building CSS in watch mode...
echo Press Ctrl+C to stop watching...
npx tailwindcss -i ./src/input.css -o ./dist/output.css --watch

pause
