# Feature Mapping & Analysis

## 1. Feature Map

### Estimates (Core)
| Feature | Endpoint / Route | Controller | View / UI Status |
|---------|-----------------|------------|------------------|
| **List (Index)** | `GET /estimates` | `EstimateController@index` | ✅ `estimates.index` (Blade) |
| **Create** | `GET /estimates/create` | `EstimateController@create` | ✅ `estimates.create` (Blade) |
| **Store** | `POST /estimates` | `EstimateController@store` | ✅ POST Action |
| **Edit** | `GET /estimates/{id}/edit` | `EstimateController@edit` | ✅ `estimates.edit` (Blade) |
| **Show (Internal)** | `GET /estimates/{id}` | `App\Livewire\Estimates\ShowEstimate` | ✅ `livewire.estimates.show-estimate` |
| **Show (Client)** | `GET /portal/estimates/{id}` | `PortalController@show` | ✅ `portal.estimates.show` |
| **Print** | `GET /.../print` | `EstimateController@print` | ✅ Window Open |
| **PDF Download** | `GET /.../pdf` | `EstimateController@downloadPdf` | ✅ Button in Toolbar |
| **Batch Download** | `POST /estimates/batch-download` | `EstimateController@batchDownload` | ⚠️ **Missing UI** (Added in this step) |
| **Preview** | `POST /estimates/preview` | `EstimateController@preview` | ⚠️ Likely used internally by PDF generation |
| **Duplicate** | `POST /.../copy` | `EstimateController@copy` | ✅ Manage Dropdown |
| **Versioning** | `POST /.../version` | `EstimateController@createVersion` | ✅ Manage Dropdown |
| **Sync Perfex** | `POST /.../sync` | `PerfexController@sync` | ✅ Manage Dropdown |
| **Followers** | `POST /.../followers` | `EstimateController@addFollower` | ✅ Sidebar Component |

### Approvals
| Feature | Route | Controller | View / UI Status |
|---------|-------|------------|------------------|
| **Submit** | `POST /.../submit` | `ApprovalController@submit` | ✅ Toolbar |
| **Approve** | `POST /.../approve` | `ApprovalController@approve` | ✅ Toolbar (Modal) |
| **Reject** | `POST /.../reject` | `ApprovalController@reject` | ✅ Toolbar (Modal) |
| **Req. Changes** | `POST /.../request-changes` | `ApprovalController@requestChanges` | ✅ Toolbar (Modal) |
| **Index** | `GET /approvals` | `ApprovalController@index` | ❓ `approvals/index` exists |

### Administration
| Feature | Route | Controller | View / UI Status |
|---------|-------|------------|------------------|
| **Automation** | `admin/automation/*` | `AutomationController` | ✅ `admin/automation/*` views exist |
| **Products** | `admin/products` | `ProductController` | ✅ `products/*` views exist |
| **Import Products** | `products.import` | `ProductController@import` | ⚠️ UI needs verification |

## 2. Identified Gaps & Dead Code
- **Redundant View**: `resources/views/estimates/show.blade.php` is unused. The application uses `App\Livewire\Estimates\ShowEstimate` which renders `livewire.estimates.show-estimate`. `show.blade.php` should be deprecated or removed.
- **Batch Download**: Route exists but no UI in `estimates.index`.
- **Product Import**: Route exists, need to verify if UI is exposed.

## 3. Improvements Made
- Added **Batch Download PDF** to Estimates Bulk Actions.
- Refactored `estimates.show` layout logic into reusable components (`quick-stats`, `toolbar`) which can be used by both Blade and Livewire (with minor adaptations).
