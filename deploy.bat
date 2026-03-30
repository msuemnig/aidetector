@echo off
setlocal

echo === Building frontend assets ===
call npm run build
if %errorlevel% neq 0 (echo BUILD FAILED & exit /b 1)

echo === Pre-computing accuracy report ===
php artisan accuracy:export
if %errorlevel% neq 0 (echo EXPORT FAILED & exit /b 1)

echo === Optimizing Laravel for production ===
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo === Deploying to AWS Lambda ===
call serverless deploy --stage production
if %errorlevel% neq 0 (echo DEPLOY FAILED & exit /b 1)

echo === Syncing static assets to S3 ===
aws s3 sync public\build s3://ai-detector-assets-production/build --delete --cache-control "public,max-age=31536000,immutable"
if %errorlevel% neq 0 (echo S3 SYNC FAILED & exit /b 1)

echo.
echo === Deploy complete ===
echo Run: serverless info --stage production --verbose
echo to get your CloudFront domain for the CNAME record.
