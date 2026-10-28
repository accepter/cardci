-- 建立 card 資料表（Google Keep 風格卡片式筆記）
CREATE TABLE IF NOT EXISTS card (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '卡片 ID',
    title      VARCHAR(255)          NULL COMMENT '卡片標題（可為空）',
    content    TEXT                  NOT NULL COMMENT '卡片內容',
    color      VARCHAR(50)  NOT NULL DEFAULT 'default' COMMENT '背景色（default/gray/yellow/green/pink/blue/purple）',
    is_pinned  TINYINT(1)   NOT NULL DEFAULT 0  COMMENT '是否釘選（1=是，0=否）',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後修改時間',
    PRIMARY KEY (id),
    INDEX idx_is_pinned_created (is_pinned DESC, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='筆記卡片資料表';
