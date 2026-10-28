SELECT 'CREATE DATABASE academy_testing'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'academy_testing')\gexec
