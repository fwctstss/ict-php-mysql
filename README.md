# GitHub Codespaces + MySQL 完整教學

**適合香港 HKDSE ICT 中四至中六｜PHP + MySQL + phpMyAdmin｜無需在學校電腦安裝 XAMPP**

這是一套課堂練習：在瀏覽器開啟雲端開發環境，以虛構的學生成績資料練習 SQL，再用 PHP 製作可搜尋、新增、修改和刪除紀錄的網頁。這份材料是配合高中程度設計的補充實作，並非考評局官方教材或完整考試範圍。

建議分三課進行，每課約 40–60 分鐘；首次下載容器映像及建置環境所需時間視網絡而定。

## 1. 先理解各工具的角色

| 工具 | 用途 | 在本練習的位置 |
|---|---|---|
| GitHub repository（儲存庫） | 保存和管理程式碼版本 | GitHub |
| Codespaces | 提供雲端 Linux、終端機及 VS Code | GitHub 雲端 |
| Docker Compose | 按設定檔啟動多個服務 | Codespace 內 |
| Apache + PHP | 接收網頁請求、執行 PHP | `web` 服務 |
| MySQL | 儲存資料、執行 SQL | `db` 服務 |
| phpMyAdmin | 用網頁管理 MySQL | `phpmyadmin` 服務 |

資料流程：

```text
學生的瀏覽器 → Codespaces 8080 → Apache/PHP → db:3306 → MySQL
學生的瀏覽器 → Codespaces 8081 → phpMyAdmin → db:3306 → MySQL
```

phpMyAdmin 是管理介面；資料實際儲存在 MySQL。PHP 在伺服器執行，瀏覽器收到它產生的 HTML。

## 2. 上課前準備

需要 GitHub 帳戶、可上網的瀏覽器，以及學校允許連接 GitHub、Codespaces、Docker 映像來源和 `*.app.github.dev` 的網絡。學校電腦不用安裝 PHP、MySQL、Docker Desktop 或 XAMPP；Docker 在雲端執行。教師宜先在學校網絡試跑一次。

個人帳戶有每月 Codespaces 計算及儲存配額；使用量受帳戶計劃、機器大小及帳單設定影響。建立前查看 GitHub 的 Billing／Usage 及預算設定，不要假設學生資格必定提供無限免費使用。[官方計費說明](https://docs.github.com/en/billing/concepts/product-billing/github-codespaces)

本專案使用 PHP 8.4、MySQL 8.4 及 phpMyAdmin 5.2 系列映像標籤。系列標籤可隨更新取得不同修補版本；教師如需各班完全相同，應先測試並記錄實際映像 digest。這裡不需要建立 `.devcontainer`，採用 Codespaces 預設開發環境，再用 Compose 啟動練習服務。[預設開發環境說明](https://docs.github.com/en/codespaces/setting-up-your-project-for-codespaces/adding-a-dev-container-configuration/introduction-to-dev-containers)

## 3. 建立儲存庫及 Codespace

1. 解壓縮專案 ZIP。
2. 在 GitHub 建立儲存庫，例如 `ict-php-mysql`，建議設為 **Private** 並加入 README。
3. 選 **Add file → Upload files**，把專案內的檔案上載至儲存庫根目錄，然後 Commit。不要把整個專案額外包在下一層資料夾。
4. `.gitignore`、`.dockerignore` 可能被檔案管理員隱藏；在 GitHub 用 **Add file → Create new file** 建立它們並貼上相應內容。尤其要確保 `.gitignore` 包含 `.env` 和 `backups/`。
5. 選 **Code → Codespaces → Create codespace on main**。若無法建立，查看帳戶配額、帳單及學校組織的限制。
6. 等待瀏覽器版 VS Code 開啟，選 **Terminal → New Terminal**。

根目錄應有以下結構：

```text
.
├── compose.yaml
├── Dockerfile
├── .dockerignore
├── .gitignore
├── .env.example
├── README.md
├── public/
│   ├── db.php
│   └── index.php
└── sql/
    └── init.sql
```

以下指令全部在 **Codespace 的 Linux 終端機**輸入，並非學校電腦的 PowerShell。

```bash
pwd
ls -a
docker --version
docker compose version
```

如果在資料夾內看不到 `compose.yaml`，先用 `cd` 進入正確資料夾。這個專案依賴預設環境的 Docker；若你使用學校自訂的開發容器，應由教師先確認 Docker 可用。

## 4. 首次啟動環境

複製環境變數範本：

```bash
cp .env.example .env
```

在左側檔案清單打開 `.env`，首次啟動前更改兩個密碼。建議使用不同的長密碼，本課可用英文字母、數字及底線，避免初學時碰到 Compose 的 `$` 插值及特殊字元解析問題。

```dotenv
MYSQL_ROOT_PASSWORD=請改成自己的管理員練習密碼
MYSQL_PASSWORD=請改成自己的學生練習密碼
```

上面中文只是佔位文字，請實際換成你選定的密碼。`.env.example` 所附密碼是公開練習值，不能當正式服務密碼。`.env` 不應 Commit；`.gitignore` 也不會自動撤回已上載到 GitHub 的檔案。

```bash
docker compose config --quiet
docker compose up -d --build
docker compose ps
```

第一次啟動會下載映像、安裝 PHP 的 `pdo_mysql` 擴充模組、建立資料庫及載入六筆資料。看到 `db` 為 `healthy`、`web` 及 `phpmyadmin` 為運行中，即可繼續。Compose 使用健康檢查等待資料表可讀取後才啟動網頁服務。

需要查看啟動情況時：

```bash
docker compose logs --tail=80 db web phpmyadmin
```

MySQL 官方映像支援以環境變數建立資料庫及使用者，並在**空白資料目錄首次初始化時**執行 `/docker-entrypoint-initdb.d` 的 SQL。[MySQL 官方映像說明](https://hub.docker.com/_/mysql)

## 5. 在瀏覽器開啟兩個介面

1. 在 VS Code 下方選 **PORTS**。看不到時，從命令面板搜尋 `Ports: Focus on Ports View`，或從 View／Open View 尋找 Ports。
2. 如未出現連接埠，選 **Add Port／Forward a Port**，分別輸入 `8080`、`8081`。
3. 保持 **Port Visibility = Private**。
4. 按連接埠旁邊的 **Open in Browser** 圖示。

| 連接埠 | 預期畫面 |
|---|---|
| `8080` | 學生成績管理網頁，預設六筆資料 |
| `8081` | phpMyAdmin 登入頁 |

使用 Codespaces 提供的網址，通常類似 `https://某個名稱-8080.app.github.dev`；不要在學校電腦的網址列直接輸入 `localhost:8080`。Private 連接埠需要你的 GitHub 身分驗證。[官方連接埠轉發說明](https://docs.github.com/en/codespaces/developing-in-a-codespace/forwarding-ports-in-your-codespace)

phpMyAdmin 登入資料：

- 使用者：`ict_student`
- 密碼：`.env` 的 `MYSQL_PASSWORD`
- 伺服器：設定檔已指定 `db`；如出現伺服器欄，填 `db`。
- 資料庫：登入後選 `ict_school`。

平日毋須使用 root。`ict_student` 在此練習資料庫有管理權限，仍可刪除資料表，操作前要看清楚 SQL。正式應用宜再按需要收窄權限。phpMyAdmin 的 `PMA_HOST` 指向 Compose 服務名稱。[官方 phpMyAdmin 映像說明](https://hub.docker.com/_/phpmyadmin)

**為甚麼主機名稱是 `db`？** 每個容器有自己的 `localhost`。PHP 若使用 `localhost`，會找自己的容器；Compose 內服務應以 `db:3306` 連接 MySQL。本專案沒有把 MySQL 的 3306 發佈為外部連接埠。

## 6. 第一課：用 phpMyAdmin 學 SQL

登入後，在左側選 `ict_school`，再選 `students`，按 **Browse／瀏覽**。選 **Structure／結構** 查看欄位，選 **SQL** 執行指令。以下指令建議逐段執行。

### 6.1 資料表與資料類型

| 欄位 | 類型 | 解釋 |
|---|---|---|
| `id` | INT UNSIGNED、AUTO_INCREMENT、PRIMARY KEY | 系統產生且唯一的學生紀錄編號 |
| `name` | VARCHAR(50)、NOT NULL | 可變長度文字，最多 50 字元 |
| `class_name` | VARCHAR(2)、NOT NULL | 如 4A、5B |
| `score` | TINYINT UNSIGNED、NOT NULL、CHECK | 整數，另以約束限制為 0–100 |

一列（row／record）是一名學生的紀錄；一欄（column／field）是一種資料。主鍵識別紀錄，姓名可以重複，因此本例不用姓名作主鍵。`NOT NULL` 不等同禁止空字串，姓名長度仍需應用程式驗證。`utf8mb4` 讓資料庫正確處理中文等 Unicode 字元。

### 6.2 查詢、篩選及排序

```sql
-- 全部資料
SELECT * FROM students;

-- 只顯示指定欄位
SELECT name, score FROM students;

-- 80 分或以上，最高分先顯示
SELECT name, class_name, score
FROM students
WHERE score >= 80
ORDER BY score DESC;

-- 4A 班且合格
SELECT * FROM students
WHERE class_name = '4A' AND score >= 50;

-- 找出姓陳的學生：% 代表零個或多個字元
SELECT * FROM students WHERE name LIKE '陳%';

-- 最高分三位；同分時以 id 排序
SELECT name, score FROM students
ORDER BY score DESC, id ASC
LIMIT 3;
```

初始資料中，80 分或以上應有李小美（92）及林嘉欣（88）。`WHERE` 先篩選紀錄，再由 `ORDER BY` 排序。

### 6.3 統計與分組

```sql
SELECT COUNT(*) AS total_students,
       ROUND(AVG(score), 2) AS average_score,
       MAX(score) AS highest_score,
       MIN(score) AS lowest_score
FROM students;

SELECT class_name,
       COUNT(*) AS total_students,
       AVG(score) AS average_score
FROM students
GROUP BY class_name;

SELECT class_name, AVG(score) AS average_score
FROM students
GROUP BY class_name
HAVING AVG(score) >= 70;
```

未改動資料時，人數為 6、平均分為 69.50、最高 92、最低 40。`WHERE` 篩選分組前的紀錄；`HAVING` 篩選分組後的結果。

### 6.4 新增、修改及刪除

```sql
INSERT INTO students (name, class_name, score)
VALUES ('測試同學', '4C', 70);

SELECT * FROM students WHERE name = '測試同學';
```

記下新增紀錄的 `id`，以下假設是 `7`；如畫面顯示其他編號，必須跟着改：

```sql
UPDATE students SET score = 75 WHERE id = 7;
SELECT * FROM students WHERE id = 7;
DELETE FROM students WHERE id = 7;
```

執行 UPDATE／DELETE 前，先以相同 WHERE 條件 SELECT 一次，確認影響哪些紀錄。省略 WHERE 會修改／刪除全表資料。刪除後 AUTO_INCREMENT 不保證重新使用舊編號；編號有空缺並非錯誤。

## 7. 第二課：理解 PHP 與 MySQL 的連接

打開 `public/db.php`。`getenv()` 讀取 Compose 傳入容器的資料庫設定；PDO 建立連線；`charset=utf8mb4` 指定連線編碼。Dockerfile 安裝 `pdo_mysql`，讓 PHP 具備 MySQL 驅動。[PHP 官方映像擴充模組說明](https://hub.docker.com/_/php)

`public/index.php` 的核心讀取步驟是：

```php
$stmt = $pdo->prepare(
    'SELECT id, name, class_name, score FROM students WHERE name LIKE ? ORDER BY id'
);
$stmt->execute(['%' . $q . '%']);
$students = $stmt->fetchAll();
```

1. `prepare()` 把 SQL 結構與輸入值分開。
2. `execute()` 將輸入作為參數傳入。
3. `fetchAll()` 取得結果陣列。
4. `foreach` 把每筆紀錄輸出為 HTML 表格欄位。

參數化查詢可避免把使用者輸入當成 SQL 語法。本例同時停用 PDO 模擬預備語句。不要把姓名等輸入直接串接進 SQL；預備語句的佔位符用於資料值，不能直接代替表格或欄位名稱。[PDO::prepare 官方手冊](https://www.php.net/manual/en/pdo.prepare.php)

網頁輸出另用 `htmlspecialchars()`，例如 `<?= h($student['name']) ?>`，把資料當文字顯示。這處理的是 HTML 輸出，不能代替 SQL 參數化查詢。

### 實際操作檢查

1. 開啟 8080，確認顯示六筆初始資料（如第一課已改動資料，數目可不同）。
2. 新增「測試同學、4C、70」，再到 phpMyAdmin 重新整理，確認紀錄存在。
3. 在網頁搜尋「測試」，應只顯示該學生。清除搜尋可顯示全部。
4. 把分數改為 75，按「儲存」，再在 phpMyAdmin 確認。
5. 按「刪除」，確認後該紀錄應消失。
6. 嘗試輸入 101、負數或小數，應被拒絕；伺服器亦會驗證，不能只依賴 HTML 的 `min`／`max`。
7. 試把姓名設為 `<b>測試</b>`，畫面應原樣顯示文字，不會變成粗體標籤；完成後刪除此測試紀錄。

### GET、POST 及表單流程

- 搜尋使用 GET，搜尋條件會出現在網址，方便重新載入。
- 新增、修改、刪除使用 POST。POST 本身不會自動防止 SQL injection。
- 表單附有 session CSRF token，伺服器驗證後才接受修改。
- 修改成功後以 HTTP 303 轉回列表，減少重新整理造成重複提交。
- 密碼從環境變數取得，資料庫詳細錯誤寫入伺服器紀錄，不直接顯示於網頁。

本例限定 4A–6F 班及 0–100 整數分數，方便課堂驗證。它沒有網站帳戶、角色權限或完整稽核功能，請只用虛構資料並保持連接埠 Private；不適合直接當學校正式成績系統。

## 8. 第三課：課堂練習與延伸

### 基礎練習

1. 列出不合格（少於 50 分）的學生。
2. 列出 5B 班學生，按姓名排序。
3. 計算合格人數。
4. 把「80 分或以上」改為「50 至 79 分」，預測會出現誰，再執行查詢。

參考答案（以原始六筆資料為準）：

```sql
SELECT name, score FROM students WHERE score < 50;
-- 張雅晴，40

SELECT * FROM students WHERE class_name = '5B' ORDER BY name;
-- 黃志明及林嘉欣；文字次序受 collation 影響

SELECT COUNT(*) AS pass_count FROM students WHERE score >= 50;
-- 5

SELECT name, score FROM students WHERE score BETWEEN 50 AND 79;
-- 陳大文 78、黃志明 65、何俊傑 54；沒有 ORDER BY 時不保證次序
```

### 程式延伸

- 加入班別下拉選單篩選；以預備語句傳入班別。
- 加入合格／不合格顯示欄，毋須另外儲存可由分數計算的結果。
- 顯示平均分；說明搜尋結果平均分與全校平均分的分別。
- 加入排序功能；用允許清單挑選 `score`／`name`，不要把網址傳來的欄位名稱直接串接到 SQL。

### 關聯式資料庫延伸

本例把班別放在學生表，方便入門。進階可另建 `classes(class_id, class_name)`，學生表儲存外鍵 `class_id`，再用 JOIN 取得班名。討論這如何減少重複班名及更新不一致。若每名學生有多科成績，應另建科目表和成績表，討論一對多／多對多關係，而非不斷增加「科目1、科目2」欄位。

### 建議學習成果

- 中四：解釋記錄、欄位、主鍵；完成 SELECT、WHERE、ORDER BY。
- 中五：使用聚合、GROUP BY、HAVING；理解表單、PHP 及 CRUD。
- 中六：解釋資料驗證、參數化查詢、關聯設計及備份，完成延伸功能。

教師可要求學生提交 SQL、查詢結果截圖、PHP 改動說明，以及對「為何 PHP 連接主機使用 db」的解釋；不要提交 `.env` 密碼。

## 9. 保存程式、備份資料及下課

### 程式碼與資料庫是兩回事

PHP 和 SQL 檔案可以 Commit 到 GitHub；MySQL 執行中的資料存在 `mysql_data` named volume，**不會因為 Git Commit 自動上載到 GitHub**。`sql/init.sql` 只是初始資料，並不是持續更新的備份。

先檢查狀態，再明確加入想保存的檔案：

```bash
git status --short
git add public/index.php public/db.php
git commit -m "Complete ICT database exercise"
git push
```

沒有改動時，Git 提示無內容可提交屬正常。不要把 `.env` 或含個人資料的匯出檔加入儲存庫。

### 備份資料

最簡單是在 phpMyAdmin 選 `ict_school → Export／匯出 → Quick → SQL`，下載 SQL 到自己可長期保存的位置。要連同所有資料表結構及資料一起匯出。

也可在 Codespace 終端機執行：

```bash
mkdir -p backups
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --no-tablespaces -u"$MYSQL_USER" "$MYSQL_DATABASE"' > backups/ict_school.sql
```

確認命令成功、檔案非空，再從 VS Code 檔案清單右鍵 `backups/ict_school.sql → Download`。不要只把備份留在同一個 Codespace。

匯入備份會改寫資料庫，先備份現況，再執行：

```bash
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql -u"$MYSQL_USER" "$MYSQL_DATABASE"' < backups/ict_school.sql
```

也可在 phpMyAdmin 選資料庫後使用 Import／匯入。匯入完成後用 SELECT 或網頁確認資料。

### 下課及下次上課

1. Commit／Push 要保存的程式碼，匯出要保存的資料庫。
2. 在命令面板選 **Codespaces: Stop Current Codespace**，或在 [Codespaces 清單](https://github.com/codespaces) 選該環境的 **Stop codespace**。
3. 下次開啟**原本的 Codespace**，到專案根目錄執行 `docker compose up -d`，再開 8080／8081。

關閉瀏覽器分頁不代表 Codespace 立即停止。停止後不再累積運算使用時間，但仍佔儲存；環境也可能因保留期限被自動刪除。刪除 Codespace 前必須 Push 程式和下載資料備份。[Codespace 生命週期](https://docs.github.com/en/codespaces/about-codespaces/understanding-the-codespace-lifecycle)

`docker compose stop` 只停止練習容器，不會停止整個 Codespace。`docker compose down` 移除容器及網絡，通常保留 named volume；但 volume 不能代替異地備份，也不保證跨 Codespace、重建或刪除後可取回。

## 10. 常見問題

| 問題 | 檢查及處理 |
|---|---|
| `Create .env first` | 在 `compose.yaml` 同一資料夾執行 `cp .env.example .env`，首次啟動前改密碼。 |
| 找不到 Compose 設定檔 | 用 `pwd`、`ls -a` 檢查是否在專案根目錄。 |
| `Cannot connect to Docker daemon` | 確認是在 Codespace 終端機。若屬學校自訂容器，請教師檢查 Docker 設定。 |
| 網頁無法開啟 | 檢查 `docker compose ps`、PORTS 的 8080／8081、GitHub 登入，以及學校網絡限制。 |
| 8080／8081 已被使用 | 用 `docker ps` 找出自己的舊練習容器；可在原專案 `docker compose down` 後重新啟動。不要停止不認識的服務。 |
| `Access denied` | 帳戶為 `ict_student`；核對首次初始化時的密碼。改 `.env` 不會改既有 MySQL 帳戶密碼。 |
| `could not find driver` | 本專案用 Dockerfile 安裝 `pdo_mysql`。執行 `docker compose up -d --build`，並用 `docker compose exec web php -m` 檢查。 |
| `Connection refused` | PHP 主機名稱應是 `db`，不是 `localhost`；查看 `docker compose logs db`。 |
| `db` unhealthy | 查看 DB logs：可能初始化失敗、空間不足、曾刪除 students 表或帳戶密碼不一致。不要第一時間刪除 volume。 |
| 修改 init.sql 後資料沒變 | 初始化 SQL 只在空白資料目錄首次執行；既有資料應用 SQL 遷移或 phpMyAdmin 修改。 |
| PHP 改動未出現 | 儲存 `public/` 內檔案並重新整理；本專案用 bind mount，通常不用重建映像。 |
| 中文亂碼 | 檔案用 UTF-8 儲存；保留 HTML charset、PDO charset 和資料庫 utf8mb4 設定。 |
| 搜尋 `%` 會列出全部 | 本例使用 LIKE，`%`／`_` 是萬用字元；參數化不會把它們改為普通字元。 |

### 需要完全重設練習時

**以下操作會刪除本專案的 MySQL named volume 及其中全部資料。先匯出並下載備份；確認只剩可刪除的練習資料才執行。**

```bash
docker compose down --volumes
docker compose up -d --build
```

重設後會以目前 `.env` 密碼和 `sql/init.sql` 重新初始化。不要把這組指令當成每天上課的正常啟動步驟。

## 11. 自我檢查

- 能分辨 repository、Codespace、PHP、MySQL 和 phpMyAdmin 的角色。
- 能在 Private 的 8080／8081 分別開啟應用和管理介面。
- 能用 SQL 查詢、統計、新增、修改、刪除資料。
- 能解釋主鍵、WHERE、預備語句及 HTML 輸出編碼。
- 能在網頁完成 CRUD，並在 phpMyAdmin 驗證同一份資料。
- 能分別保存程式碼及匯出資料庫。
- 能停止 Codespace，並知道關閉分頁與停止服務的分別。

## 驗證範圍

交付前已靜態檢查設定內容、檔案結構及壓縮包內容。本機 Compose 工具無法正常執行，且沒有運行中的 Docker daemon 或 PHP CLI，因此未完成 Compose 解析、容器啟動或 PHP 執行測試。首次使用前請教師先執行 `docker compose config --quiet`，再依第 7 節操作檢查，在 Codespaces 完成驗收。
