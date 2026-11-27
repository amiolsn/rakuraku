-- ユーザーテーブルにis_activeを追加
ALTER TABLE users
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
-- 教員テーブルにis_activeを追加
ALTER TABLE teachers
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
-- 科目テーブルにis_activeを追加
ALTER TABLE subjects
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;