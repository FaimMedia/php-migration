CREATE TABLE IF NOT EXISTS "always_run" (
	"col1" VARCHAR(255) NOT NULL,
	"col2" VARCHAR(255) NOT NULL,
	PRIMARY KEY ("col1")
);

INSERT INTO "always_run" VALUES
	('key1', 'Test value 1'),
	('key2', 'Test value 2')
ON CONFLICT ("col1") DO UPDATE SET "col2" = EXCLUDED."col2";
