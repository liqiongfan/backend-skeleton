**www_label**
---
| 序号 | 字段名称 | 类型 | 长度 | 允许 NULL | 默认值 | 备注 | 
| :---: | --- | --- | :---: | :---: | :---: | --- | 
|  1 | id         | integer | 11 | N |   |       | 
|  2 | alias      | string  | 20 | N |   | 别名 | 
|  3 | name       | string  | 20 | N |   | 推送位名称 | 
|  4 | frequency  | integer | 11 | N | 0 | 权重 | 
|  5 | ordering   | integer | 11 | N | 0 | 排序 | 
|  6 | enabled    | tinyint | 1  | N | 1 | 激活 | 
|  7 | created_at | integer | 11 | N |   | 添加时间 | 
|  8 | created_by | integer | 11 | N |   | 添加人 | 
|  9 | updated_at | integer | 11 | N |   | 更新时间 | 
| 10 | updated_by | integer | 11 | N |   | 更新人 | 
