# MEBFloatingText

[English](#english) · [中文](#中文) 

PocketMine-MP 5 floating text plugin.

| | |
|---|---|
| API | `5.0.0` |
| Load | `POSTWORLD` |

---

## English

A floating text (hologram) plugin for PocketMine-MP 5.

### What it does

Places multi-line floating text in the world — spawn announcements, warp signs, leaderboards, server status boards, and the like.

- **Multi-line layout** — one floating text holds many lines; add, edit, insert and delete them individually instead of rewriting the whole thing.
- **Alignment** — left / center / right. Computed from character-width metrics (`Render/FontMetrics.php`) and padded with spaces for visual alignment.
- **Automatic word wrap** — set a pixel width cap per floating text and long lines wrap on their own; `0` disables it.
- **Adjustable line spacing** — 0.05 to 2.0, default 0.28.
- **Dynamic placeholders** — write `{online}`, `{tps}` and friends into the text and they refresh on the configured interval. Each player is sent their own private entity, so per-player placeholders such as `{player}` and `{ping}` resolve correctly for everyone.
- **Colors and line breaks** — in commands, use `&` in place of `§` for colors and `\n` for a line break.
- **GUI management** — everything is reachable through forms; a player typing bare `/mebft` opens the main menu (requires MEBForms).
- **Bilingual** — `zh_CN` / `en_US`, switchable in game and written back to the config.
- **Distance-based sending** — floating texts beyond the view range (48 blocks by default) are never sent to the client, cutting pointless traffic.
- **Show / hide** — temporarily hide a floating text without deleting its data.

### Commands

Main command `/mebft`, aliases `/ft` and `/floatingtext`. Every subcommand also has a Chinese alias.

A player running bare `/mebft` gets the GUI（requires MEBForms）; from console it prints the help.

| Subcommand | Aliases | Usage | Description | Permission |
|---|---|---|---|---|
| `help` | `?` `帮助` | `/mebft help` | Show command help | Everyone |
| `gui` | `ui` `menu` `界面` | `/mebft gui` | Open the graphical interface (player only) | Everyone |
| `create` | `add` `创建` | `/mebft create <id> <text>` | Create a floating text at your position (player only) | OP |
| `remove` | `del` `delete` `删除` | `/mebft remove <id>` | Delete a floating text | OP |
| `list` | `ls` `列表` | `/mebft list [page]` | Paginated list of all floating texts | Everyone |
| `info` | `show` `详情` | `/mebft info <id>` | Show world, position, layout and every line | Everyone |
| `line` | `行` | `/mebft line <id> <add\|set\|insert\|del> [line] [text]` | Append / set / insert / delete a line | OP |
| `align` | `对齐` | `/mebft align <id> <left\|center\|right>` | Set the alignment | OP |
| `width` | `宽度` | `/mebft width <id> <width\|0>` | Set the wrap width in pixels, `0` disables wrapping (non-zero must be ≥ 16) | OP |
| `spacing` | `行间距` | `/mebft spacing <id> <spacing>` | Set the line spacing, range 0.05 - 2.0 | OP |
| `move` | `tp` `移动` | `/mebft move <id> [x] [y] [z]` | Move to your position; coords default to where you stand, console must give all three | OP |
| `visible` | `toggle` `显示` | `/mebft visible <id> [on\|off]` | Show or hide; toggles when the argument is omitted | OP |
| `vars` | `var` `变量` | `/mebft vars` | List every available placeholder | Everyone |
| `lang` | `language` `语言` | `/mebft lang <language code>` | Switch language (`zh_CN` / `en_US`), persisted to the config | OP |
| `reload` | `重载` | `/mebft reload` | Re-read the config and floating text data | OP |

Permission nodes:

- `MEBFloatingText.ge` — default `true`, grants use of `/mebft` itself.
- `MEBFloatingText.op` — default `op`, grants the subcommands marked OP above.

An id may only contain letters, digits, underscores and dashes, 1-32 characters.

### Placeholders

Write these into the text; they are substituted on every refresh:

| Placeholder | Meaning |
|---|---|
| `{player}` | Player name |
| `{online}` | Online player count |
| `{max}` | Server max players |
| `{world}` | World the player is in |
| `{tps}` | Server TPS |
| `{load}` | Server load percentage |
| `{ping}` | Player ping (ms) |
| `{time}` | Current time, H:i:s |
| `{date}` | Current date |
| `{money}` | Player money (requires MEBSociety, returns 0 without it) |
| `{line}` | A divider line |
| `{br}` | Line break |

### Dependencies (optional)

There are no hard dependencies — install this plugin alone and every core feature works. The two plugins below are `softdepend`: installing them unlocks extra features, and leaving them out changes nothing else.

| Plugin | Unlocks | Without it |
|---|---|---|
| **MEBForms** | The whole GUI: main menu, create, list, per-text management, layout tuning, delete confirmation, placeholder reference, language switcher | GUI unavailable, one warning at startup, all commands unaffected |
| **MEBSociety** | The `{money}` placeholder, showing a player's balance inside a floating text | That placeholder stays 0, every other placeholder works |

Both are detected purely at runtime: MEBForms through `FormFactory::isAvailable()`, MEBSociety by looking up a class-name string in `PlaceholderResolver`. Neither is referenced at compile time, so a missing plugin never throws.

### Config and data

Data folder `plugin_data/MEBFloatingText/`:

- `Settings.yml` — settings. The keys are Chinese; missing keys are filled in with defaults on startup.
- `FloatingTexts.yml` — floating text data. Corrupted entries are skipped with a log message and left untouched in the file.

`Settings.yml` defaults:

| Key | Default | Meaning |
|---|---|---|
| 语言 | `zh_CN` | Language code |
| 可视距离 | 48 | Beyond this distance (blocks) nothing is sent; 0 means unlimited |
| 距离检测间隔(s) | 1 | Visibility check period |
| 变量刷新间隔(s) | 5 | Resend period for texts containing placeholders |
| 进服延迟显示(tick) | 30 | Delay before sending to a player who just joined |
| 创建时的高度偏移 | 1.6 | Height above the creator's feet |
| 浮空字数量上限 | 200 | Maximum number of floating texts |
| 列表每页显示数量 | 8 | List entries per page |
| 默认对齐方式 | `center` | Default alignment |
| 默认最大宽度 | 0 | Default wrap width, 0 disables |
| 默认行间距 | 0.28 | Default line spacing |
| 时区 | `Asia/Shanghai` | Affects `{time}` and `{date}` |

### Notes

- Floating texts are per-player fake entities (`Network/TextActor.php`, `Network/TextView.php`). They cost no server-side entities, which is also what makes player-specific placeholders possible.
- A single `RefreshTask` drives both the visibility and the placeholder refresh cycles, so only one scheduler task is registered.
- Rendering lives in `Render/`: `FontMetrics` measures character widths, `TextRenderer` handles wrapping and alignment, `FormatState` carries color codes across wrapped lines.
- Language files are in `resources/language/`. Command usages and descriptions are read from language keys (`usage_<subcommand>` / `desc_<subcommand>`), so adding a language means adding one yml.
- On disable and on `reload`, old entities are despawned from clients before being rebuilt; a hot reload re-sends everything to players already online.
---

## 中文

PocketMine-MP 5 浮空字插件。

### 功能

在世界里放置多行浮空文字，用来做出生点公告、传送点标牌、排行榜、服务器状态板这类东西。

- **多行排版**：一条浮空字包含多行文本，可以按行增删改，不用整条重写。
- **对齐方式**：左对齐 / 居中 / 右对齐。基于字宽度量（`Render/FontMetrics.php`）计算，用空格补位实现视觉对齐。
- **自动折行**：给一条浮空字设定像素宽度上限，超长的行自动换行；设为 0 关闭该功能。
- **行间距可调**：0.05 - 2.0，默认 0.28。
- **动态变量**：文本里写 `{online}`、`{tps}` 这类占位符，按配置的间隔自动刷新。因为每个玩家看到的是独立实体，所以 `{player}`、`{ping}` 这种玩家相关的变量也能正确显示各自的值。
- **颜色与换行**：指令里用 `&` 代替 `§` 写颜色，用 `\n` 换行。
- **GUI 管理**：整套功能都有GUI表单界面，直接输 `/mebft` 就会打开主菜单（需要安装 MEBForms）。
- **中英双语**：`zh_CN` / `en_US`，可在游戏内实时切换。
- **按距离下发**：超出可视距离（默认 48 格）的浮空字不会发包给客户端。
- **显示 / 隐藏**：不删除数据的前提下临时显示/隐藏某条浮空字。

### 指令

主指令 `/mebft`，别名 `/ft`、`/floatingtext`。所有子指令都有中文别名。

玩家不带参数执行 `/mebft` 会直接打开 GUI（需要安装 MEBForms）；控制台执行则输出帮助。

| 子指令 | 别名 | 用法 | 说明 | 权限 |
|---|---|---|---|---|
| `help` | `?` `帮助` | `/mebft help` | 查看指令帮助 | 所有人 |
| `gui` | `ui` `menu` `界面` | `/mebft gui` | 打开图形界面（仅玩家） | 所有人 |
| `create` | `add` `创建` | `/mebft create <id> <文本>` | 在你脚下创建浮空字（仅玩家） | OP |
| `remove` | `del` `delete` `删除` | `/mebft remove <id>` | 删除一条浮空字 | OP |
| `list` | `ls` `列表` | `/mebft list [页码]` | 分页查看所有浮空字 | 所有人 |
| `info` | `show` `详情` | `/mebft info <id>` | 查看某条的世界、坐标、排版、逐行内容 | 所有人 |
| `line` | `行` | `/mebft line <id> <add\|set\|insert\|del> [行号] [文本]` | 追加 / 修改 / 插入 / 删除某一行 | OP |
| `align` | `对齐` | `/mebft align <id> <left\|center\|right>` | 设置对齐方式 | OP |
| `width` | `宽度` | `/mebft width <id> <像素宽度\|0>` | 设置自动折行宽度，0 为不折行（非 0 时不得小于 16） | OP |
| `spacing` | `行间距` | `/mebft spacing <id> <行间距>` | 设置行间距，范围 0.05 - 2.0 | OP |
| `move` | `tp` `移动` | `/mebft move <id> [x] [y] [z]` | 移到你所在位置；省略坐标时用当前位置，控制台必须填完整坐标 | OP |
| `visible` | `toggle` `显示` | `/mebft visible <id> [on\|off]` | 显示或隐藏，省略参数则切换 | OP |
| `vars` | `var` `变量` | `/mebft vars` | 列出所有可用变量 | 所有人 |
| `lang` | `language` `语言` | `/mebft lang <语言代码>` | 切换语言（`zh_CN` / `en_US`），会写回配置 | OP |
| `reload` | `重载` | `/mebft reload` | 重新读取配置与浮空字数据 | OP |

权限节点：

- `MEBFloatingText.ge` — 默认 `true`，使用 `/mebft` 指令本身。
- `MEBFloatingText.op` — 默认 `op`，执行上表中标为 OP 的子指令。

id 只能包含字母、数字、下划线、减号，长度 1-32。

### 文本变量

写在浮空字内容里，刷新时自动替换：

| 变量 | 含义 |
|---|---|
| `{player}` | 玩家名 |
| `{online}` | 在线人数 |
| `{max}` | 服务器最大人数 |
| `{world}` | 玩家所在世界名 |
| `{tps}` | 服务器 TPS |
| `{load}` | 服务器负载百分比 |
| `{ping}` | 玩家延迟（ms） |
| `{time}` | 当前时间 时:分:秒 |
| `{date}` | 当前日期 |
| `{money}` | 玩家游戏币（需要 MEBSociety，缺失时返回 0） |
| `{line}` | 一条分割线 |
| `{br}` | 换行 |

### 依赖（可选）

本插件没有硬依赖，单独安装即可使用全部核心功能。下面两个都是 `softdepend`，装上会解锁额外功能，不装也不影响插件正常运行：

| 插件 | 装上解锁 | 不装时 |
|---|---|---|
| **MEBForms** | 整套 GUI 表单：主菜单、创建、列表、详情管理、排版调整、删除确认、变量说明、语言切换 | GUI 不可用，启动时给一条警告，全部指令功能不受影响 |
| **MEBSociety** | `{money}` 变量，可在浮空字里显示玩家游戏币 | 该变量恒为 0，其余变量正常 |

两者都是纯运行时检测：MEBForms 通过 `FormFactory::isAvailable()` 判断，MEBSociety 在 `PlaceholderResolver` 里按类名字符串查找，源码中没有在编译期引用，所以插件缺失不会报错。

### 配置与数据

数据目录 `plugin_data/MEBFloatingText/`：

- `Settings.yml` — 配置，键名是中文，缺失键在启动时自动补齐。
- `FloatingTexts.yml` — 浮空字数据。数据损坏的条目会被跳过并在日志中提示，文件内容保留不动。

`Settings.yml` 默认值：

| 键 | 默认 | 说明 |
|---|---|---|
| 语言 | `zh_CN` | 语言代码 |
| 可视距离 | 48 | 超出该距离（方块）不下发，0 为不限制 |
| 距离检测间隔(s) | 1 | 可见性检查周期 |
| 变量刷新间隔(s) | 5 | 含变量的浮空字重发周期 |
| 进服延迟显示(tick) | 30 | 玩家进服后延迟多久下发 |
| 创建时的高度偏移 | 1.6 | 相对创建者脚下的高度 |
| 浮空字数量上限 | 200 | |
| 列表每页显示数量 | 8 | |
| 默认对齐方式 | `center` | |
| 默认最大宽度 | 0 | 0 为不折行 |
| 默认行间距 | 0.28 | |
| 时区 | `Asia/Shanghai` | 影响 `{time}` `{date}` |

### 提示

- 浮空字用逐玩家下发的伪实体（`Network/TextActor.php`、`Network/TextView.php`）实现，不占用服务端实体，也因此支持玩家专属变量。
- 一个 `RefreshTask` 里同时跑可见性刷新和变量刷新两种周期，避免注册多个定时器。
- 文本渲染在 `Render/`：`FontMetrics` 量字宽，`TextRenderer` 做折行与对齐，`FormatState` 跨行继承颜色格式码。
- 语言文件在 `resources/language/`，指令用法与说明都从语言键（`usage_<子指令>` / `desc_<子指令>`）读取，加新语言只要加一个 yml。
- 插件禁用和 `reload` 时会先撤掉客户端上的旧实体再重建，热重载时会给已在线玩家补发一次。

---
