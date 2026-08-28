# 更新日志

本文件记录 MEBFloatingText 的版本变更。

## [1.2.0]

配合 [MEBTerritory](https://github.com/MengBaofh/MEBTerritory)（领地系统）的功能版本：新增浮空字归属权与插件托管机制，并开放公开 API。

存量数据无需迁移，直接替换插件即可。老数据缺少「所有者」「托管插件」两个键时按服务器公共浮空字处理。

### 归属权

每条浮空字新增可选的户主，管理权限判定如下。

| 浮空字 | 可执行隐藏 / 移动 / 删除 / 修改样式 |
| --- | --- |
| 无户主（服务器公共） | 最高权限、OP |
| 有户主 | 户主本人、最高权限（OP 不可） |
| 被插件托管 | 同上，且内容不可手动修改 |

- 最高权限的判定满足以下任一条件即可：控制台、持有 `MEBFloatingText.master` 权限节点、为 MEBSociety 的「最高权限」。
- 手动创建的浮空字不记录户主，归属服务器公共。如需指定归属，使用 `/mebft owner`。

### 插件托管

- 浮空字可标记为由指定插件托管，内容由该插件生成并刷新。
- 托管中的浮空字不允许手动修改内容，其余操作（隐藏、移动、删除）不受限制。

### 新增指令

| 子指令 | 别名 | 用法 | 说明 | 权限 |
| --- | --- | --- | --- | --- |
| `owner` | `归属` `户主` | `/mebft owner <id> [玩家名\|-]` | 查看或修改浮空字户主，`-` 为改回服务器公共 | 查看：所有人<br>修改：**仅最高权限** |

### 新增权限节点

| 节点 | 默认 | 说明 |
| --- | --- | --- |
| `MEBFloatingText.master` | `false` | 管理任意浮空字，包括修改归属 |

### 公开 API

新增 `MengBao\MEBFloatingText\API\FloatingTextAPI`，供其他插件调用。

```php
use MengBao\MEBFloatingText\API\FloatingTextAPI;

if (FloatingTextAPI::isAvailable()) {
    FloatingTextAPI::put("territory_7", $worldName, $position, $lines, "mengbao", "MEBTerritory");
}
```

| 方法 | 说明 |
| --- | --- |
| `isAvailable()` | MEBFloatingText 是否已装且已启用 |
| `put($id, $world, $position, $lines, $owner, $managedBy)` | 新建或更新一条托管浮空字 |
| `setLines($id, $lines)` | 仅更新内容 |
| `remove($id)` / `exists($id)` / `get($id)` | 删除 / 判断存在 / 获取对象 |
| `setOwner($id, $owner)` | 修改户主，`null` 为改回服务器公共 |
| `listManagedIds($pluginName)` | 获取指定插件托管的全部 id |
| `listOwnedIds($playerName)` | 获取指定玩家名下的全部 id |

- `put()` 为「新建或更新」语义。目标已存在时保留其对齐、宽度、行间距与显示开关。
- 更新时若世界发生变化，先移除旧世界中的实体。
- 未安装 MEBFloatingText 时 `isAvailable()` 返回 `false`，其余方法返回 `false` 或空数组，不抛出异常。建议以 `softdepend` 方式引用。

### 其他改动

- GUI 按归属显示按钮：无管理权限的浮空字不显示管理按钮，托管中的浮空字不显示「编辑文本内容」按钮，列表中标注户主名。
- GUI 的修改操作在提交时重新校验权限。
- `info` 与 GUI 详情新增「户主」「托管插件」两行。
- 语言键缺失时回退至插件包内置语言文件，升级后新增的语言键无需手动补齐。

### 数据变更

`FloatingTexts.yml` 的每条浮空字新增两个可选键。

| 键 | 含义 |
| --- | --- |
| 所有者 | 户主（小写玩家名），`~` 为服务器公共浮空字 |
| 托管插件 | 托管插件名，`~` 为无托管 |
