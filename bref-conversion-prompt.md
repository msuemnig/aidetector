# Task: Convert Laravel App to Serverless (Bref + Lambda + CloudFront + S3)

## Context
This is a Laravel application for AI content detection. It has no database currently, and should only get one added if you discover it is genuinely required (e.g., storing results, user sessions that can't use cookies, etc.). The app is nearly complete and traditionally structured — your job is to make it serverless-ready without changing any business logic.

## Goals
1. Install and configure **Bref** for AWS Lambda
2. Set up **Serverless Framework** deployment config (`serverless.yml`)
3. Reconfigure the app for the Lambda execution environment (read-only filesystem, ephemeral `/tmp`, no persistent processes)
4. Set up **S3 + CloudFront** for static asset delivery
5. Only add **PlanetScale** (free tier, MySQL-compatible) if a database is genuinely needed after auditing the app

---

## Step-by-Step Instructions

### 1. Audit the App First
Before making any changes, read through the following and report your findings:
- All routes in `routes/web.php` and `routes/api.php`
- All controllers in `app/Http/Controllers/`
- The `.env.example` file
- `config/filesystems.php`, `config/session.php`, `config/cache.php`
- `config/queue.php`
- Any existing migrations in `database/migrations/`

From this audit, answer:
- Does the app write to the local filesystem anywhere (uploads, logs, temp files)?
- Does the app use sessions? If so, what driver?
- Does the app use queues?
- Does the app use scheduled commands?
- Is a database actually used or just scaffolded?

Report these findings before proceeding.

---

### 2. Install Bref

```bash
composer require bref/bref bref/laravel-bridge --with-all-dependencies
php artisan vendor:publish --tag=serverless-config
```

Install the Serverless Framework globally (confirm it's available or install it):
```bash
npm install -g serverless
```

---

### 3. Create `serverless.yml`

Replace or configure the generated `serverless.yml` with the following structure. Fill in any app-specific environment variables you discovered in the audit:

```yaml
service: ai-detection-app

provider:
  name: aws
  region: us-east-1
  runtime: provided.al2
  environment:
    APP_ENV: production
    APP_KEY: ${ssm:/ai-detection/APP_KEY}
    LOG_CHANNEL: stderr
    SESSION_DRIVER: cookie
    CACHE_DRIVER: array
    VIEW_COMPILED_PATH: /tmp/storage/framework/views
    FILESYSTEM_DISK: s3
    AWS_BUCKET: !Ref StaticAssetsBucket
    # Add any other discovered env vars here

plugins:
  - ./vendor/bref/bref

functions:
  web:
    handler: public/index.php
    timeout: 28
    layers:
      - ${bref:layer.php-83-fpm}
    events:
      - httpApi: '*'

  # Only include this if queues are used:
  # worker:
  #   handler: artisan
  #   timeout: 300
  #   layers:
  #     - ${bref:layer.php-83}
  #     - ${bref:layer.console}
  #   events:
  #     - sqs:
  #         arn: !GetAtt JobsQueue.Arn
  #         batchSize: 1

  # Only include if scheduled commands are used:
  # artisan:
  #   handler: artisan
  #   timeout: 120
  #   layers:
  #     - ${bref:layer.php-83}
  #     - ${bref:layer.console}
  #   events:
  #     - schedule:
  #         rate: rate(5 minutes)
  #         input: '"schedule:run"'

resources:
  Resources:
    StaticAssetsBucket:
      Type: AWS::S3::Bucket
      Properties:
        BucketName: ai-detection-app-assets-${sls:stage}
        PublicAccessBlockConfiguration:
          BlockPublicAcls: true
          BlockPublicPolicy: true
```

---

### 4. Fix the Filesystem

Lambda's filesystem is **read-only except for `/tmp`**. Make the following changes:

**a) Modify `bootstrap/app.php`** to redirect writable paths to `/tmp`:

```php
// In bootstrap/app.php, before the app is returned, add:
if (isset($_ENV['LAMBDA_TASK_ROOT'])) {
    $_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';

    $dirs = [
        '/tmp/storage/framework/views',
        '/tmp/storage/framework/cache',
        '/tmp/storage/logs',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
```

**b) Update `config/view.php`**:
```php
'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views')),
```

**c) Update `config/logging.php`**:
Set the default log channel to `stderr` for Lambda (CloudWatch captures it automatically):
```php
'default' => env('LOG_CHANNEL', 'stderr'),
```

---

### 5. Fix Sessions and Cache

**Sessions** — switch to cookie-based unless the audit reveals a reason not to:
- Set `SESSION_DRIVER=cookie` in `.env.example` and `serverless.yml`
- If the app stores anything sensitive in session that's too large for a cookie, use DynamoDB driver instead and document it as a TODO with setup instructions

**Cache** — for low traffic with no caching needs, `CACHE_DRIVER=array` is fine (per-request, no persistence). If caching is needed across requests, note that ElastiCache (Redis) would be needed and flag it.

---

### 6. Set Up S3 + CloudFront for Static Assets

**a) Install Flysystem S3 adapter**:
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

**b) Verify or add the `s3` disk in `config/filesystems.php`**:
```php
's3' => [
    'driver'     => 's3',
    'key'        => env('AWS_ACCESS_KEY_ID'),
    'secret'     => env('AWS_SECRET_ACCESS_KEY'),
    'region'     => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket'     => env('AWS_BUCKET'),
    'url'        => env('ASSET_URL'),
    'visibility' => 'public',
],
```

**c) Add an asset sync step to the deploy process.** Add a note in `DEPLOYMENT.md` that static assets must be synced after each build:
```bash
npm run build
aws s3 sync public/build s3://ai-detection-app-assets-production/build --delete
```

**d) Set `ASSET_URL`** in `serverless.yml` to the CloudFront distribution URL once it's created. Add a placeholder for now:
```yaml
ASSET_URL: https://YOUR_CLOUDFRONT_URL
```

---

### 7. Database — Only If Needed

Review the audit findings. **Only proceed with this section if the app genuinely requires a database.**

If needed:

**a) No extra package required** — standard Laravel MySQL/PDO works with PlanetScale. Add SSL compatibility to `config/database.php` under the `mysql` connection:
```php
'options' => [
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
],
```

**b) Update `.env.example`**:
```
DB_CONNECTION=mysql
DB_HOST=aws.connect.psdb.cloud
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-pscale-password
MYSQL_ATTR_SSL_CA=/etc/ssl/cert.pem
```

**c) Add to `serverless.yml` environment**:
```yaml
DB_CONNECTION: mysql
DB_HOST: ${ssm:/ai-detection/DB_HOST}
DB_DATABASE: ${ssm:/ai-detection/DB_DATABASE}
DB_USERNAME: ${ssm:/ai-detection/DB_USERNAME}
DB_PASSWORD: ${ssm:/ai-detection/DB_PASSWORD}
```

> **Note**: PlanetScale free tier does not support foreign key constraints. If any migrations use `$table->foreign()`, remove them or replace with application-level enforcement. Flag any that exist.

If a database is **not** needed, skip this entire section and confirm that clearly in your response.

---

### 8. Update `.gitignore` and `.env.example`

Add to `.gitignore`:
```
.serverless/
serverless.yml.bak
```

Update `.env.example` with all new variables, using safe placeholder values.

---

### 9. Final Checklist

After all changes, confirm each of the following:

- [ ] `composer require bref/bref bref/laravel-bridge` completed successfully
- [ ] `serverless.yml` is present and valid YAML
- [ ] No code writes to `storage/` or `bootstrap/cache/` without going through `/tmp` on Lambda
- [ ] `LOG_CHANNEL=stderr` is set
- [ ] `SESSION_DRIVER=cookie` (or documented alternative)
- [ ] `VIEW_COMPILED_PATH` redirects to `/tmp`
- [ ] Static assets are configured for S3 delivery
- [ ] Database section completed only if genuinely needed
- [ ] `.env.example` updated
- [ ] `DEPLOYMENT.md` created with full deploy instructions

---

### 10. Create `DEPLOYMENT.md`

Create a `DEPLOYMENT.md` in the project root covering:

- **Prerequisites**: AWS CLI configured, Serverless Framework installed, Node/NPM
- **First-time setup**: SSM parameter creation, S3 bucket, CloudFront distribution
- **Deploy command**: `serverless deploy --stage production`
- **Asset deploy**: `npm run build && aws s3 sync public/build s3://BUCKET/build --delete`
- **Run migrations** (if applicable): `serverless invoke -f artisan --data '"migrate --force"'`
- **Tail logs**: `serverless logs -f web --tail`

---

## Constraints
- Do **not** change any business logic, UI, or AI detection functionality
- Do **not** add complexity that isn't warranted by the app's actual needs
- Keep costs as close to $0/month as possible for low traffic
- Flag anything that cannot be done automatically and requires manual AWS console steps
