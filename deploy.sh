#!/bin/bash
set -e

echo "=== Building frontend assets ==="
npm run build

echo "=== Pre-computing accuracy report ==="
php artisan accuracy:export

echo "=== Optimizing Laravel for production ==="
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "=== Deploying to AWS Lambda ==="
serverless deploy --stage production

echo "=== Syncing static assets to S3 ==="
BUCKET=$(serverless info --stage production --verbose 2>&1 | grep "AssetsBucketName:" | awk '{print $2}')
if [ -z "$BUCKET" ]; then
    echo "ERROR: Could not determine S3 bucket name. Check serverless output."
    exit 1
fi
aws s3 sync public/build "s3://${BUCKET}/build" --delete --cache-control "public,max-age=31536000,immutable"

echo "=== Invalidating CloudFront cache ==="
DIST_ID=$(serverless info --stage production --verbose 2>&1 | grep "CloudFrontDistributionId:" | awk '{print $2}')
if [ -n "$DIST_ID" ]; then
    aws cloudfront create-invalidation --distribution-id "$DIST_ID" --paths "/*" > /dev/null
    echo "CloudFront invalidation submitted for ${DIST_ID}"
fi

echo ""
echo "=== Deploy complete ==="
CF_DOMAIN=$(serverless info --stage production --verbose 2>&1 | grep "CloudFrontDomain:" | awk '{print $2}')
echo "CloudFront domain: ${CF_DOMAIN}"
echo "Create a CNAME record pointing your domain to: ${CF_DOMAIN}"
