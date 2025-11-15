-- Additional Veggit tables
-- Depends on base PHP-Auth schema

PRAGMA foreign_keys = ON;

-- -------------------------------------------------------
-- User Profile Information
-- -------------------------------------------------------
CREATE TABLE "user_info" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "user_id" INTEGER NOT NULL CHECK ("user_id" >= 0),
    "bio" TEXT DEFAULT NULL,
    "profile_picture_url" TEXT DEFAULT NULL COLLATE NOCASE CHECK (LENGTH("profile_picture_url") <= 500),
    CONSTRAINT "user_info_user_id_uq" UNIQUE ("user_id"),
    CONSTRAINT "user_info_user_id_fk" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

CREATE INDEX "user_info_user_id_ix" ON "user_info" ("user_id");


-- -------------------------------------------------------
-- Post Categories
-- -------------------------------------------------------
CREATE TABLE "categories" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "name" TEXT NOT NULL COLLATE NOCASE CHECK (LENGTH("name") <= 100),
    "description" TEXT DEFAULT NULL
);

-- Insert some predefined categories
INSERT INTO "categories" ("name", "description") VALUES
    -- Original practical categories
    ('News', 'Latest news and updates'),
    ('Tutorials', 'Guides and tutorials'),
    ('Recipes', 'Vegetarian and vegan recipes'),
    ('Tips', 'General tips and tricks'),
    ('Community', 'Community stories and events'),

    -- Fun / joke / meme categories
    ('ShowerThoughts', 'Random musings and clever thoughts'),
    ('Aww', 'Cute animals and heartwarming content'),
    ('Memes', 'Funny memes and jokes'),
    ('UnpopularOpinions', 'Share your unpopular opinions'),
    ('Jokes', 'Humor and lighthearted content'),

    -- Technical / programming categories
    ('Programming', 'Coding, software development, and tutorials'),
    ('DevOps', 'Infrastructure, deployment, and automation'),
    ('WebDev', 'Web development tips and tricks'),
    ('DataScience', 'Analytics, machine learning, and AI'),
    ('Cybersecurity', 'Security news and advice'),

    -- Gaming / entertainment categories
    ('Gaming', 'All things gaming: tips, news, and memes'),
    ('BoardGames', 'Discussion about board games and tabletop'),
    ('Esports', 'Competitive gaming news and highlights'),
    ('LetsPlays', 'Gameplay videos and streams'),
    ('GameDev', 'Game development and design tips');


-- -------------------------------------------------------
-- User Posts
-- -------------------------------------------------------
CREATE TABLE "user_posts" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "title" TEXT NOT NULL COLLATE NOCASE CHECK (LENGTH("title") <= 200),
    "short_description" TEXT DEFAULT NULL CHECK (LENGTH("short_description") <= 500),
    "media_url" TEXT DEFAULT NULL COLLATE NOCASE CHECK (LENGTH("media_url") <= 500),
    "date_published" INTEGER NOT NULL CHECK ("date_published" >= 0),
    "author_id" INTEGER NOT NULL CHECK ("author_id" >= 0),
    "category_id" INTEGER DEFAULT NULL CHECK ("category_id" >= 0),
    "content" TEXT DEFAULT NULL,
    CONSTRAINT "user_posts_author_fk" FOREIGN KEY ("author_id") REFERENCES "users" ("id") ON DELETE CASCADE,
    CONSTRAINT "user_posts_category_fk" FOREIGN KEY ("category_id") REFERENCES "categories" ("id") ON DELETE SET NULL
);

-- -------------------------------------------------------
-- User Comments
-- -------------------------------------------------------
CREATE TABLE "comments" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "post_id" INTEGER NOT NULL CHECK ("post_id" >= 0),
    "author_id" INTEGER NOT NULL CHECK ("author_id" >= 0),
    "content" TEXT NOT NULL CHECK (LENGTH("content") <= 1000),
    "date_added" INTEGER NOT NULL CHECK ("date_added" >= 0),
    CONSTRAINT "comments_post_fk" FOREIGN KEY ("post_id") REFERENCES "user_posts" ("id") ON DELETE CASCADE,
    CONSTRAINT "comments_author_fk" FOREIGN KEY ("author_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

CREATE TABLE invite_keys (
    key TEXT PRIMARY KEY,
    use_count INTEGER DEFAULT 0
);

CREATE INDEX "user_posts_author_id_ix" ON "user_posts" ("author_id");
CREATE INDEX "user_posts_date_published_ix" ON "user_posts" ("date_published");
CREATE INDEX "user_posts_category_id_ix" ON "user_posts" ("category_id");
CREATE INDEX "comments_post_id_ix" ON "comments" ("post_id");
CREATE INDEX "comments_author_id_ix" ON "comments" ("author_id");
CREATE INDEX "comments_date_added_ix" ON "comments" ("date_added");