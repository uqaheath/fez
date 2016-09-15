UPDATE fez_config SET config_value = 'true' WHERE config_name = 'aws_enabled';
UPDATE fez_config SET config_value = 'true' WHERE config_name = 'aws_s3_enabled';
UPDATE fez_config SET config_value = '%AWS_ACCESS_KEY_ID%' WHERE config_name = 'aws_key';
UPDATE fez_config SET config_value = '%AWS_SECRET_ACCESS_KEY%' WHERE config_name = 'aws_secret';
UPDATE fez_config SET config_value = '%FEZ_S3_BUCKET%' WHERE config_name = 'aws_s3_bucket';
UPDATE fez_config SET config_value = '%FEZ_S3_CACHE_BUCKET%' WHERE config_name = 'aws_s3_cache_bucket';
UPDATE fez_config SET config_value = '%FEZ_S3_SRC_PREFIX%' WHERE config_name = 'aws_s3_src_prefix';
UPDATE fez_config SET config_value = '%FEZ_S3_BUCKET%' WHERE config_name = 'aws_s3_san_import_bucket';
UPDATE fez_config SET config_value = '%AWS_CLOUDFRONT_FILE_SERVE_URL%' WHERE config_name = 'aws_file_serve_url';
UPDATE fez_config SET config_value = '%AWS_CLOUDFRONT_PRIVATE_KEY_FILE%' WHERE config_name = 'aws_cf_private_key_file';
UPDATE fez_config SET config_value = '%AWS_CLOUDFRONT_KEY_PAIR_ID%' WHERE config_name = 'aws_cf_key_pair_id';