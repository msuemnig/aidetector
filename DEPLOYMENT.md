# Deployment Guide

## Architecture

```
User → CloudFront (your-domain)
         ├── /build/* → S3 bucket (static JS/CSS)
         └── /* → API Gateway → Lambda (PHP 8.4 via Bref)
```

## Prerequisites

- AWS CLI configured with credentials (`aws configure`)
- Node.js 18+ and npm
- PHP 8.4 with Composer
- Serverless Framework: `npm install -g serverless`

## First-Time Setup

### 1. Store secrets in AWS SSM Parameter Store

```bash
# Generate a Laravel app key
php artisan key:generate --show

# Store it in SSM (replace the value)
aws ssm put-parameter --name "/ai-detector/APP_KEY" --type SecureString --value "base64:YOUR_KEY_HERE"

# Set your app URL (your custom domain)
aws ssm put-parameter --name "/ai-detector/APP_URL" --type String --value "https://your-domain.example.com"

# Set asset URL (will be your CloudFront URL — update after first deploy)
aws ssm put-parameter --name "/ai-detector/ASSET_URL" --type String --value "https://placeholder.cloudfront.net"
```

### 2. First deploy

```bash
bash deploy.sh
```

This will:
1. Build frontend assets (Vite)
2. Pre-compute the accuracy report JSON
3. Deploy Lambda function + API Gateway + S3 bucket + CloudFront
4. Sync static assets to S3

### 3. After first deploy

The deploy script outputs the CloudFront domain. You need to:

1. **Update ASSET_URL** in SSM to the CloudFront domain:
   ```bash
   aws ssm put-parameter --name "/ai-detector/ASSET_URL" --type String --value "https://YOUR_CF_DOMAIN.cloudfront.net" --overwrite
   ```

2. **Update APP_URL** in SSM to your custom domain:
   ```bash
   aws ssm put-parameter --name "/ai-detector/APP_URL" --type String --value "https://your-domain.example.com" --overwrite
   ```

3. **Create DNS CNAME** in Route53:
   - Record name: `your-subdomain`
   - Type: CNAME
   - Value: the CloudFront distribution domain (e.g. `d1234abcd.cloudfront.net`)

4. **Redeploy** to pick up the updated SSM values:
   ```bash
   bash deploy.sh
   ```

## Subsequent Deploys

```bash
bash deploy.sh
```

## What's deployed where

| Component | Location |
|---|---|
| PHP application | AWS Lambda (via Bref) |
| Static assets (JS/CSS) | S3 → CloudFront CDN |
| Accuracy report data | `storage/accuracy-report.json` (baked into Lambda package) |
| Word frequency list | `resources/data/common_words.txt` (baked into Lambda package) |
| Test fixtures | **NOT deployed** (dev only) |

## Estimated Cost (low traffic)

- Lambda: Free tier covers 1M requests/month
- S3: ~$0.01/month for static assets
- CloudFront: Free tier covers 1TB/month
- SSM: Free for standard parameters
- **Total: ~$0-2/month**

## Troubleshooting

**Cold starts:** First request after idle may take 2-3 seconds. Subsequent requests are fast.

**View cache errors:** Lambda writes compiled views to `/tmp`. If you see view errors, redeploy.

**Asset 404s:** Run `aws s3 sync public/build s3://BUCKET/build --delete` manually.

**Logs:** `serverless logs -f web --stage production`
