# Kế hoạch triển khai theo yêu cầu mentor — Zigbee Smart Building Automation

**Ngày tổng hợp:** 2026-05-11  
**Nguồn kết hợp:**

- File tổng hợp trước: `mentor_requirements_zigbee_automation.md`
- Sync-up mới nhất: `subtitles (1).txt`
- Context dự án hiện tại: MQTT contract, cloud backend plan, native gateway boundary, file tree hiện có

> Ghi chú: transcript bị nhiễu âm và sai chính tả do auto-subtitle. Nội dung dưới đây là bản diễn giải kỹ thuật theo ngữ cảnh dự án hiện tại, chỉ tập trung vào yêu cầu của mentor và kế hoạch triển khai. Toàn bộ phần prompt/agent prompt của file trước đã được loại bỏ.

---

## 1. Tóm tắt định hướng mới nhất

Mentor đang yêu cầu chuyển trọng tâm từ việc **chỉ bật/tắt đèn thủ công** sang tính năng **Automation**.

Hiện tại hệ thống đã có hoặc đang đạt được mức:

```text
App / API có thể điều khiển bật/tắt light
Cloud / Gateway có luồng command cơ bản
```

Việc tiếp theo cần làm là:

```text
1. Bổ sung phần theo dõi trạng thái device
2. Tập trung làm Automation với priority cao
3. Cho người dùng tạo rule kiểu: nếu event này xảy ra thì chạy action kia
4. App tạo rule → Cloud lưu DB → Cloud gửi xuống Gateway → Gateway xử lý rule
5. Các phần login, phân quyền, UI nâng cao để sau
```

Câu chốt kỹ thuật:

```text
Automation = người dùng nối một event của device A với một action của device B.
Ví dụ: switch pressed → light toggle / light on.
```

---

## 2. Các yêu cầu của mentor

### 2.1. Yêu cầu 1 — Hoàn thiện điều khiển và theo dõi trạng thái trước

Mentor ghi nhận app/API hiện đã có thể bật/tắt đèn ở mức cơ bản. Tuy nhiên, sau phần điều khiển cần bổ sung thêm phần **theo dõi trạng thái**.

#### Ý nghĩa kỹ thuật

Không chỉ gửi command xuống light, hệ thống còn phải hiển thị được:

- Light đang `on` hay `off`
- Device có `reachable` hay không
- Command đã `executed`, `failed`, hay `timeout`
- State mới nhất đã được cloud nhận chưa
- App/dashboard có đọc được state mới nhất không

#### Kết quả cần thấy

```text
User bật/tắt light trên app
→ command gửi xuống gateway
→ light đổi trạng thái
→ gateway/cloud cập nhật reported state
→ app hiển thị lại trạng thái mới
```

#### Phần được làm rõ hơn ở sync-up mới

Trước đó mentor nói nhiều về Automation. Sync-up mới làm rõ thứ tự ưu tiên hơn:

```text
Control light OK
→ thêm status monitoring
→ sau đó tập trung Automation
```

Tức là không nên nhảy thẳng sang automation nếu app/cloud vẫn chưa hiển thị được trạng thái device sau điều khiển.

---

### 2.2. Yêu cầu 2 — Automation là trọng tâm chính tiếp theo

Mentor nhấn mạnh phần Automation là phần có giá trị hơn so với chỉ bật/tắt thiết bị.

Automation ban đầu không cần quá thông minh. Chỉ cần người dùng tạo được logic kiểu:

```text
Nếu switch được bấm
→ bật/tắt light
```

Hoặc:

```text
Nếu motion sensor báo có người
→ bật light
```

#### Ý nghĩa kỹ thuật

App không chỉ là remote control. App phải có một màn hình hoặc flow cho phép người dùng tạo “kịch bản tự động”.

Kịch bản đó cần có tối thiểu:

| Thành phần | Vai trò | Ví dụ |
|---|---|---|
| Trigger device | Thiết bị tạo sự kiện | `switch-01`, `pir-01` |
| Event / Condition | Điều kiện kích hoạt | `pressed`, `occupied`, `unoccupied` |
| Target device | Thiết bị nhận hành động | `light-01` |
| Action | Việc cần làm | `on`, `off`, `toggle` |
| Name | Tên rule để quản lý | `Switch toggles light` |
| Enabled | Bật/tắt rule | `true/false` |

#### Phần được làm rõ hơn ở sync-up mới

Sync-up mới nói rõ mentor muốn mô hình:

```text
Người dùng có thể nối một event có sẵn với một action có sẵn.
```

Đây là bản chất của Automation trong phạm vi hiện tại, không phải một rule engine tổng quát phức tạp.

---

### 2.3. Yêu cầu 3 — Phải định nghĩa sẵn event cho từng loại device

Mentor yêu cầu trước mắt nên thiết kế các event có sẵn cho từng loại device, không để người dùng tự nhập tự do.

#### Event nên hard-code giai đoạn đầu

| Device type | Event được phép chọn | Ghi chú |
|---|---|---|
| `switch` | `pressed` | Khi người dùng bấm nút switch |
| `motion` | `occupied` | PIR phát hiện có người |
| `motion` | `unoccupied` | PIR hết phát hiện sau timeout |
| `light` | `state_on`, `state_off` | Có thể dùng để debug hoặc future rule, chưa bắt buộc phase đầu |

#### Action nên hard-code giai đoạn đầu

| Target device type | Action được phép chọn | Mapping kỹ thuật |
|---|---|---|
| `light` | `on` | Zigbee On/Off Cluster command `On` |
| `light` | `off` | Zigbee On/Off Cluster command `Off` |
| `light` | `toggle` | Zigbee On/Off Cluster command `Toggle` |

#### Phần được làm rõ hơn ở sync-up mới

File trước đã nói “dùng select option cứng”. Sync-up mới làm rõ hơn: không chỉ UI cứng, mà cả **event/action catalog** cũng phải định nghĩa cứng trước.

Nói cách khác, phase đầu không làm kiểu:

```text
User tự nhập condition bất kỳ
User tự nhập action bất kỳ
```

Mà làm kiểu:

```text
When device: [switch-01]
Event:       [pressed]
Then device: [light-01]
Action:      [toggle]
```

---

### 2.4. Yêu cầu 4 — Không làm UI Automation quá động

Mentor nhấn mạnh nhiều lần rằng thời gian không còn nhiều, nên không nên làm UI quá động hoặc quá tổng quát.

#### Hướng làm đúng

- Dùng dropdown/select option có sẵn
- Chỉ support vài case đầu tiên
- Không làm rule builder kéo-thả
- Không làm UI tự generate theo mọi device type
- Không cần icon/animation phức tạp
- Không cần dashboard đẹp kiểu production

#### UI tối thiểu

```text
Tab Devices
- List device
- State hiện tại
- Nút điều khiển light on/off nếu cần

Tab Automation
- List automation
- Form tạo automation đơn giản
- Enable/disable/delete nếu kịp
```

#### Phần được làm rõ hơn ở sync-up mới

Sync-up mới tiếp tục xác nhận: “đầu tiên phải hard-code vài cái thôi”. Điều này làm rõ rằng MVP Automation nên được thiết kế như một flow cố định để chạy được, không phải một automation builder tổng quát.

---

### 2.5. Yêu cầu 5 — Rule sau khi tạo phải được lưu ở Cloud DB

Mentor yêu cầu sau khi người dùng chốt rule trên app, rule phải được đưa lên Cloud và lưu trong database.

#### Luồng bắt buộc

```text
User tạo automation trên app
→ App gọi Cloud API
→ Cloud validate rule
→ Cloud lưu rule vào DB
→ Cloud trả response cho app
```

#### Cloud DB cần có ít nhất

Bảng `automations`:

| Field | Ý nghĩa |
|---|---|
| `id` | ID của automation |
| `name` | Tên automation |
| `enabled` | Rule có đang bật không |
| `trigger_device_id` | Device phát sinh event |
| `trigger_device_type` | Loại device trigger |
| `trigger_event` | Event kích hoạt |
| `target_device_id` | Device nhận action |
| `target_device_type` | Loại target |
| `action_command` | `on/off/toggle` |
| `gateway_id` | Gateway nhận rule |
| `sync_status` | `pending/synced/failed` |
| `created_at` | Thời điểm tạo |
| `updated_at` | Thời điểm cập nhật |

#### Phần được làm rõ hơn ở sync-up mới

Sync-up mới xác nhận rõ hơn đường đi dữ liệu:

```text
Chọn rule trên app
→ đưa vào cloud database
→ gửi xuống gateway xử lý
```

Điểm này biến automation từ “rule local trong gateway” thành “user-configurable rule được quản lý bởi cloud”.

---

### 2.6. Yêu cầu 6 — Cloud phải gửi rule xuống Gateway

Cloud không chỉ lưu rule. Sau khi rule được tạo/sửa/xóa, Cloud phải gửi bản tin xuống Gateway để Gateway biết và thực thi.

#### Luồng bắt buộc

```text
Cloud DB lưu automation
→ Cloud publish message xuống Gateway
→ Gateway nhận automation upsert/delete/enable/disable
→ Gateway lưu local rule
→ Gateway reply sync result về Cloud
```

#### Message cần thiết giữa Cloud và Gateway

Tối thiểu cần các operation:

| Operation | Mục đích |
|---|---|
| `automation.upsert` | Tạo mới hoặc cập nhật rule |
| `automation.delete` | Xóa rule khỏi gateway |
| `automation.enable` | Bật rule |
| `automation.disable` | Tắt rule |

#### Khuyến nghị topic

Vì MQTT contract hiện tại đã có command lifecycle, giai đoạn đầu nên dùng topic command có sẵn:

```text
sb/v1/{tenant}/{site}/{gateway}/commands/{command_id}/request
sb/v1/{tenant}/{site}/{gateway}/commands/{command_id}/reply
```

Payload phân biệt bằng trường `op`:

```json
{
  "op": "automation.upsert",
  "automation_id": "auto-001",
  "rule": {
    "name": "Switch toggles light",
    "enabled": true,
    "condition": {
      "device_id": "switch-01",
      "device_type": "switch",
      "event": "pressed"
    },
    "action": {
      "target_device_id": "light-01",
      "target_device_type": "light",
      "command": "toggle"
    }
  }
}
```

#### Phần được làm rõ hơn ở sync-up mới

Sync-up mới nhấn mạnh cần “lên message” cho phần action/automation binding. Nghĩa là phải có contract rõ ràng cho bản tin Cloud → Gateway, không chỉ làm API trên cloud.

---

### 2.7. Yêu cầu 7 — Gateway phải xử lý Automation, không chỉ forward command

Mentor yêu cầu Gateway phải là nơi nhận rule và xử lý rule khi có event.

#### Gateway cần có module Automation Manager

Trách nhiệm:

- Nhận rule từ Cloud
- Lưu rule local
- Update/delete/enable/disable rule
- Nhận device event/report
- Kiểm tra condition có match không
- Execute action nếu match
- Gửi command xuống target device
- Ghi log và publish kết quả lên Cloud

#### Luồng xử lý trong Gateway

```text
Device event/report đi vào Gateway
→ Automation Manager nhận event
→ duyệt local rule table
→ rule.enabled == true?
→ condition match?
→ tạo action command
→ gửi xuống target device
→ publish automation event/log/reply
```

#### Phần được làm rõ hơn ở sync-up mới

File trước đã nói cần Gateway Automation Manager. Sync-up mới làm rõ hơn rằng Gateway phải xử lý chính phần “nối event với action”, tức là Cloud tạo rule nhưng execution logic nằm ở Gateway.

---

### 2.8. Yêu cầu 8 — Phân biệt Automation Binding với Zigbee Binding Cluster

Trong transcript có nhiều chỗ mentor dùng từ “binding” hoặc “nối hai con với nhau”. Trong phạm vi yêu cầu hiện tại, nên hiểu đây là **application-level automation binding**, không phải nhất thiết dùng Zigbee Binding Cluster.

#### Phân biệt

| Khái niệm | Ý nghĩa | Có nên dùng cho phase này? |
|---|---|---|
| Zigbee Binding Cluster | Device A gửi command trực tiếp cho device B trong Zigbee network | Không phải trọng tâm hiện tại |
| Automation Binding | Cloud/App tạo rule: event của A → action của B, Gateway thực thi | Trọng tâm hiện tại |

#### Lý do

Mentor nói đến:

```text
App chọn rule
Cloud lưu database
Cloud gửi xuống Gateway
Gateway xử lý
```

Nếu dùng Zigbee Binding Cluster trực tiếp thì Cloud/App khó quản lý rule động theo database. Vì vậy phase hiện tại nên triển khai **Automation Binding ở tầng Gateway/Cloud**, còn Zigbee Binding Cluster có thể để future optimization.

---

### 2.9. Yêu cầu 9 — Dashboard/debug chỉ cần đơn giản

Mentor cho phép làm dashboard hoặc debug page, nhưng không cần phức tạp.

#### Dashboard tối thiểu

| Khu vực | Nội dung |
|---|---|
| Device state | Light/motion/switch hiện tại |
| Gateway status | Online/offline, last seen |
| Command status | accepted/queued/sent/executed/failed/timeout |
| Automation rules | Rule đang có trên cloud/gateway |
| Automation events | Rule nào match, action nào chạy |
| Raw log | Topic/payload gần nhất nếu cần debug |

#### Không cần làm ngay

- Chart phức tạp
- WebSocket realtime nếu polling đủ dùng
- Dashboard chỉnh rule đầy đủ
- UI đẹp kiểu sản phẩm thương mại

#### Phần được làm rõ hơn ở sync-up mới

Sync-up mới tập trung nhiều vào app và automation, không nhắc thêm yêu cầu dashboard phức tạp. Vì vậy dashboard vẫn chỉ nên là debug tool phụ, không phải trọng tâm.

---

### 2.10. Yêu cầu 10 — Login/phân quyền để sau

Mentor nói sau khi xong phần Automation thì sau này có thể làm tiếp các tính năng chuẩn như phân quyền, đăng nhập.

#### Ý nghĩa

Không nên đưa auth/login/permission vào phase đầu của Automation nếu nó làm chậm MVP.

#### Thứ tự đúng

```text
1. Device control + status
2. Automation rule create/sync/execute
3. E2E evidence
4. Sau đó mới login/permission/role
```

#### Phần được làm rõ hơn ở sync-up mới

Sync-up mới nói rõ các phần như phân quyền/đăng nhập là feature “sau này”. Đây là cơ sở để loại chúng khỏi scope P0.

---

## 3. Những điểm đã được làm rõ hơn ở sync-up mới

| Nội dung | File trước đã có | Sync-up mới làm rõ thêm | Kết luận triển khai |
|---|---|---|---|
| Trạng thái hiện tại | Đã có điều khiển/app/API ở mức nào đó | App/API bật tắt light đã tương đối OK | Bổ sung status monitoring trước khi vào automation sâu |
| Ưu tiên tiếp theo | Automation là trọng tâm | Automation có priority cao hơn phần khác | P0 phải là automation, không sa vào UI đẹp |
| Event/action | Có condition/action | Event phải được định nghĩa sẵn theo device type | Tạo event/action catalog cứng |
| UI | Không làm quá động | Phase đầu hard-code vài rule/pair | Form automation đơn giản với dropdown |
| Luồng dữ liệu | App → Cloud → Gateway | Cloud DB và message xuống Gateway được nhấn mạnh | Cloud phải có DB + sync message |
| Gateway | Cần Automation Manager | Gateway là nơi xử lý nối event với action | Implement local rule store + event matching |
| Binding | Có nhắc binding/kịch bản | Là nối event/action ở application layer | Không ưu tiên Zigbee Binding Cluster phase này |
| Feature sau | Dashboard/advanced để sau | Login/phân quyền để sau | Auth không thuộc P0 |

---

## 4. Scope MVP được chốt lại

### 4.1. In scope — phải làm

- App hiển thị device status cơ bản
- App tạo automation bằng form hard-code
- Cloud API CRUD automation
- Cloud DB lưu automation
- Cloud sync automation xuống Gateway
- Gateway lưu rule local
- Gateway match event và execute action
- Gateway/Cloud/App hiển thị trạng thái rule và execution result
- Test bằng Postman/curl và E2E evidence

### 4.2. Out of scope — để sau

- Full dynamic rule builder
- Multi-condition phức tạp
- Multi-action nâng cao
- Scene/group nâng cao
- Auth/login/role/permission
- Dashboard production-level
- WebSocket realtime nếu polling đủ dùng
- Zigbee Binding Cluster automation động
- Thêm nhiều loại node mới ngoài scope light/switch/motion

---

## 5. Kế hoạch theo phase

## Phase 0 — Re-baseline trạng thái hiện tại

### Mục tiêu

Xác nhận chính xác project hiện đã làm được gì trước khi thêm Automation.

### Việc cần làm

#### 0.1. Kiểm tra luồng điều khiển light

Xác nhận:

```text
App/API → Cloud command → MQTT → Gateway → Zigbee light → ACK/reported state
```

Checklist:

- [ ] App hoặc API gửi được command `on/off/toggle`
- [ ] Gateway nhận được command
- [ ] Light đổi trạng thái thật
- [ ] Gateway publish command reply
- [ ] Cloud cập nhật command status
- [ ] App đọc lại được trạng thái mới

#### 0.2. Kiểm tra luồng state/report

Checklist:

- [ ] Gateway publish `devices/light/{device_id}/reported`
- [ ] Cloud subscriber ghi vào `device_states`
- [ ] `GET /api/devices/{id}/state` trả state mới nhất
- [ ] App hiển thị state mới nhất

#### 0.3. Kiểm tra luồng event

Checklist:

- [ ] Switch event hoặc motion event có topic/event payload rõ ràng
- [ ] Cloud ghi được event vào DB
- [ ] Gateway log có event source rõ ràng

### Output phase 0

- Bảng trạng thái hiện tại: done / partial / missing
- Danh sách API đã có
- Danh sách MQTT topic đang chạy thật
- Danh sách event thực tế đang có từ device

### Exit criteria

Có thể chứng minh ít nhất:

```text
Light command chạy được
Light state đọc lại được từ cloud/app
```

---

## Phase 1 — Hoàn thiện status monitoring

### Mục tiêu

Sau khi điều khiển device, app/cloud phải biết device đang ở trạng thái nào.

### Việc cần làm

#### 1.1. Chuẩn hóa state model

State tối thiểu:

```json
{
  "device_id": "light-01",
  "device_type": "light",
  "state": {
    "power": "on",
    "reachable": true
  },
  "reported_at": "2026-05-11T00:00:00Z"
}
```

#### 1.2. Cloud API

Bổ sung hoặc kiểm tra:

| API | Mục đích |
|---|---|
| `GET /api/devices` | List devices |
| `GET /api/devices/{id}` | Device detail |
| `GET /api/devices/{id}/state` | State mới nhất |
| `GET /api/events?device_id=` | Event log |
| `GET /api/commands/{command_id}` | Command status |

#### 1.3. App UI

Tab Devices cần hiển thị:

- Device name
- Device type
- Current state
- Reachable/unreachable
- Last updated
- Command status gần nhất nếu có

### Output phase 1

- App có status card cho light
- Cloud API trả state đúng
- Postman collection có các request status

### Exit criteria

```text
Bấm ON/OFF trên app
→ light đổi trạng thái
→ app thấy state mới mà không cần đoán thủ công
```

---

## Phase 2 — Chốt Automation Contract và Data Model

### Mục tiêu

Trước khi code sâu, phải khóa schema rule, event catalog, action catalog, API contract, MQTT payload.

### Việc cần làm

#### 2.1. Chốt event catalog

```json
{
  "switch": ["pressed"],
  "motion": ["occupied", "unoccupied"],
  "light": ["state_on", "state_off"]
}
```

Phase đầu chỉ bắt buộc:

```text
switch.pressed
motion.occupied
motion.unoccupied nếu đã có event thật
```

#### 2.2. Chốt action catalog

```json
{
  "light": ["on", "off", "toggle"]
}
```

#### 2.3. Chốt automation schema

```json
{
  "automation_id": "auto-001",
  "name": "Switch toggles light",
  "enabled": true,
  "condition": {
    "device_id": "switch-01",
    "device_type": "switch",
    "event": "pressed"
  },
  "action": {
    "target_device_id": "light-01",
    "target_device_type": "light",
    "command": "toggle",
    "params": {}
  },
  "gateway_id": "gw-ubuntu-01",
  "sync_status": "pending"
}
```

#### 2.4. Chốt Cloud → Gateway operation

Tối thiểu:

```text
automation.upsert
automation.delete
automation.enable
automation.disable
```

#### 2.5. Chốt reply status

```text
accepted | synced | failed | deleted | enabled | disabled
```

### Output phase 2

- `docs/AUTOMATION_CONTRACT.md`
- JSON examples cho create/update/delete
- MQTT payload examples cho Cloud → Gateway và Gateway → Cloud
- Event/action catalog được ghi rõ

### Exit criteria

Không còn mơ hồ về:

```text
Rule gồm field gì
Cloud gửi gì xuống Gateway
Gateway reply gì về Cloud
Event nào được phép trigger
Action nào được phép chạy
```

---

## Phase 3 — Cloud Automation API + Database

### Mục tiêu

Cloud quản lý được automation rule và sync được rule xuống Gateway.

### Việc cần làm

#### 3.1. Database

Thêm bảng `automations`:

```sql
CREATE TABLE automations (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  enabled BOOLEAN NOT NULL DEFAULT true,
  trigger_device_id TEXT NOT NULL,
  trigger_device_type TEXT NOT NULL,
  trigger_event TEXT NOT NULL,
  trigger_value TEXT NULL,
  target_device_id TEXT NOT NULL,
  target_device_type TEXT NOT NULL,
  action_command TEXT NOT NULL,
  action_params JSON NULL,
  gateway_id TEXT NOT NULL,
  sync_status TEXT NOT NULL DEFAULT 'pending',
  last_sync_error TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

Thêm bảng `automation_events` nếu đủ thời gian:

```sql
CREATE TABLE automation_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  automation_id TEXT NOT NULL,
  event_type TEXT NOT NULL,
  status TEXT NOT NULL,
  reason TEXT NULL,
  payload JSON NULL,
  occurred_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

#### 3.2. API

| Method | Endpoint | Mục đích | P0/P1 |
|---|---|---|---|
| `GET` | `/api/automations` | List rule | P0 |
| `GET` | `/api/automations/{id}` | Detail | P0 |
| `POST` | `/api/automations` | Create + sync | P0 |
| `PUT` | `/api/automations/{id}` | Update + sync | P0 |
| `DELETE` | `/api/automations/{id}` | Delete + sync | P0 |
| `POST` | `/api/automations/{id}/enable` | Enable | P1 |
| `POST` | `/api/automations/{id}/disable` | Disable | P1 |
| `GET` | `/api/automation-events` | Execution log | P1 |

#### 3.3. Validation

Cloud phải reject rule nếu:

- Trigger device không tồn tại
- Target device không tồn tại
- Event không nằm trong catalog
- Action không nằm trong catalog
- Target device type không support action
- Gateway ID thiếu hoặc không match device

#### 3.4. Sync xuống Gateway

Khi create/update/delete:

```text
1. Save DB row
2. Publish automation operation xuống Gateway
3. Mark sync_status = pending
4. Khi Gateway reply OK, update sync_status = synced
5. Nếu Gateway reply failed/timeout, update sync_status = failed
```

### Output phase 3

- Cloud DB migration/schema
- Router/API automation
- Pydantic schemas
- MQTT publish automation operations
- Postman collection cho automation API

### Exit criteria

```text
POST /api/automations
→ DB có row
→ MQTT message automation.upsert được publish
→ Gateway có thể nhận hoặc ít nhất log được message
```

---

## Phase 4 — Gateway Automation Manager

### Mục tiêu

Gateway nhận rule từ Cloud, lưu local, và dùng rule đó để tự động điều khiển device khi event xảy ra.

### Việc cần làm

#### 4.1. Local rule store

Giai đoạn đầu có thể dùng JSON file:

```text
gateway_automation_rules.json
```

Ví dụ:

```json
{
  "rules": [
    {
      "automation_id": "auto-001",
      "name": "Switch toggles light",
      "enabled": true,
      "condition": {
        "device_id": "switch-01",
        "device_type": "switch",
        "event": "pressed"
      },
      "action": {
        "target_device_id": "light-01",
        "target_device_type": "light",
        "command": "toggle"
      }
    }
  ]
}
```

#### 4.2. Automation operation handler

Gateway cần xử lý:

| Operation | Gateway action |
|---|---|
| `automation.upsert` | Validate + lưu/update local rule |
| `automation.delete` | Xóa local rule |
| `automation.enable` | Set enabled=true |
| `automation.disable` | Set enabled=false |

#### 4.3. Event matching

Pseudo logic:

```text
on_device_event(event):
  for rule in rules:
    if rule.enabled == false:
      skip
    if event.device_id != rule.condition.device_id:
      skip
    if event.event != rule.condition.event:
      skip
    execute_action(rule.action)
    publish automation event
```

#### 4.4. Action execution

Phase đầu chỉ support light:

| Action | Gateway command |
|---|---|
| `on` | Send On/Off Cluster `On` |
| `off` | Send On/Off Cluster `Off` |
| `toggle` | Send On/Off Cluster `Toggle` |

#### 4.5. Logging

Gateway cần log:

- Rule upsert/delete
- Rule validation failed
- Event received
- Rule matched
- Rule skipped + reason nếu cần debug
- Action sent
- Action result executed/failed/timeout

### Output phase 4

- Gateway Automation Manager
- Local rule persistence
- Event matching
- Action execution
- Automation event publish/log

### Exit criteria

```text
Cloud gửi automation.upsert
→ Gateway lưu rule
→ Switch event xảy ra
→ Gateway match rule
→ Light toggle thật
→ Cloud/App thấy event/action result
```

---

## Phase 5 — App Automation UI MVP

### Mục tiêu

Người dùng tạo được automation rule từ app bằng UI đơn giản.

### Việc cần làm

#### 5.1. Devices tab

Hiển thị:

- Device list
- Device state
- Light control button
- Last updated

#### 5.2. Automation tab

Form hard-code:

```text
Automation name: [Switch toggles light]
When device:     [switch-01]
Event:           [pressed]
Then device:     [light-01]
Action:          [toggle]
[Save]
```

Danh sách rule:

```text
[ON] Switch toggles light
Condition: switch-01 pressed
Action: light-01 toggle
Sync: synced
Last run: executed / failed / never
```

#### 5.3. Không làm trong phase này

- Login
- Permission
- Dynamic nested condition
- Multi-action editor
- Drag/drop UI
- Scene builder đầy đủ

### Output phase 5

- App tạo được rule
- App list được rule
- App thấy sync status
- App thấy rule enabled/disabled nếu đã support

### Exit criteria

```text
Người dùng tạo rule trên app
→ Cloud lưu rule
→ Gateway nhận rule
→ Rule chạy khi event xảy ra
```

---

## Phase 6 — E2E test và evidence

### Mục tiêu

Chứng minh toàn bộ luồng Automation chạy thật.

### Test case 1 — Switch toggles light

```text
Given:
- switch-01 joined
- light-01 joined
- automation auto-001 enabled

When:
- User presses switch-01

Expected:
- Gateway receives switch.pressed event
- Automation Manager matches auto-001
- Gateway sends toggle command to light-01
- Light state changes
- Cloud receives reported state
- App shows updated state
```

### Test case 2 — Motion turns on light

```text
Given:
- pir-01 joined
- light-01 joined
- automation auto-002 enabled

When:
- PIR reports occupied

Expected:
- Gateway receives motion.occupied event
- Automation Manager matches auto-002
- Gateway sends light.on
- Light turns on
- Cloud event log records automation execution
```

### Test case 3 — Create rule from Cloud/App then execute

```text
Given:
- Gateway online
- Cloud API running
- MQTT connected

When:
- App/Postman sends POST /api/automations

Expected:
- Cloud creates automation row
- Cloud publishes automation.upsert
- Gateway stores rule locally
- Gateway replies synced/failed
- Event later triggers the rule
```

### Evidence cần lưu

- Postman collection
- API response screenshots
- MQTT trace
- Gateway log
- Cloud DB rows
- App screenshots
- Video ngắn rule chạy thật nếu cần báo cáo

### Exit criteria

Có thể chứng minh bằng log/video/API:

```text
App tạo rule
→ Cloud lưu rule
→ Gateway nhận rule
→ Device event xảy ra
→ Gateway execute action
→ Cloud/App thấy kết quả
```

---

## Phase 7 — Sau MVP

### Chỉ làm sau khi Automation MVP chạy ổn

| Feature | Lý do để sau |
|---|---|
| Login/auth | Mentor đã nói có thể làm sau |
| Permission/role | Chưa cần cho demo automation |
| Dashboard đẹp | Không phải trọng tâm |
| WebSocket realtime | Polling đủ dùng cho MVP |
| Multi-condition | Làm phức tạp schema và UI |
| Multi-action | Dễ tăng bug khi chưa có core ổn định |
| Scene/group nâng cao | Có thể phát triển từ automation sau |
| Zigbee Binding Cluster động | Không phù hợp luồng Cloud DB → Gateway hiện tại ở MVP |

---

## 6. Priority checklist

### P0 — Bắt buộc

- [ ] Xác nhận light control path đã chạy
- [ ] Xác nhận app/cloud đọc được device state
- [ ] Chốt event/action catalog cứng
- [ ] Chốt automation schema
- [ ] Thêm Cloud DB table `automations`
- [ ] Thêm API CRUD automation
- [ ] Cloud publish automation operation xuống Gateway
- [ ] Gateway nhận và lưu local rule
- [ ] Gateway match event → execute action
- [ ] App có Automation tab đơn giản
- [ ] E2E test `switch.pressed → light.toggle`

### P1 — Nên làm

- [ ] Motion automation: `occupied → light.on`
- [ ] `unoccupied → light.off`
- [ ] Enable/disable rule
- [ ] Delete rule sync xuống Gateway
- [ ] Automation event log
- [ ] Dashboard/debug card cho automation
- [ ] Command/automation status hiển thị rõ trên app

### P2 — Để sau

- [ ] Login/auth
- [ ] Permission/role
- [ ] Dynamic rule builder
- [ ] Multi-condition/multi-action
- [ ] Full scene/group system
- [ ] Dashboard production-level
- [ ] WebSocket realtime

---

## 7. Kết luận triển khai

Mentor hiện đang muốn team đi theo hướng:

```text
Điều khiển device chạy được
→ Status monitoring rõ ràng
→ Automation rule đơn giản nhưng chạy thật
→ Cloud quản lý rule
→ Gateway thực thi rule
→ App hiển thị được kết quả
```

Phần được làm rõ nhất ở sync-up mới là:

```text
Automation phase đầu không phải làm hệ thống rule tổng quát.
Chỉ cần định nghĩa sẵn vài event/action, cho người dùng chọn để nối chúng với nhau,
lưu rule vào Cloud DB, gửi xuống Gateway, rồi Gateway xử lý khi event xảy ra.
```

