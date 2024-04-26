CREATE TABLE IF NOT EXISTS "test" (
	"col1" VARCHAR(255) NOT NULL,
	"col2" VARCHAR(255) NOT NULL,
	"col3" BOOLEAN NOT NULL DEFAULT TRUE,
	PRIMARY KEY ("col1")
);

INSERT INTO "test" VALUES (
	'Test col 1',
	'Test col 2',
	FALSE
);

INSERT INTO "test" VALUES (
	'Test col 2',
	'Duplicate primary key error',
	TRUE
);

INSERT INTO "test" VALUES (
	'Test col 3',
	'Test',
	FALSE
);
