<?php

/**
 * Role permission management view.
 *
 * Expected variables:
 *   $role     Role           the role (with permissions loaded)
 *   $filter   array<string,string>
 *   $paginate array{items,total,page,per_page,last_page,role}
 *   $_csrf    string
 */

declare(strict_types=1);

/** @var \PHPAdmin\Modules\Access\Models\Role $role */
/** @var array<string,string> $filter */
/** @var array{datas:list<\PHPAdmin\Modules\Access\Models\Permission>,paginate_data:array{total_data:int,page_size:int,current_page:int,total_page:int}} $paginate */

$_pd = $paginate['paginate_data'];

$pageUrl = static function (int $p) use ($filter, $role): string {
    $params = array_filter(
        array_merge($filter, ['q_page' => (string)$p]),
        static fn($v) => $v !== '' && $v !== null
    );
    return route('admin.v1.access.role.permission', ['id' => $role->id]) . '?' . http_build_query($params);
};
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800">Permission Management</h1>
</div>

<div class="tw-card p-0 overflow-hidden">
  <div class="px-6 py-4 border-b flex items-center justify-between">
    <h2 class="text-lg font-bold" style="color:var(--primary)">Permission List</h2>
    <div class="btn-group btn-sm">
      <button type="submit" form="selection"
              formmethod="post"
              formaction="<?= e(route('admin.v1.access.role.permission.assign_selected', ['id' => $role->id])) ?>"
              data-confirm="Confirm Assign"
              class="btn btn-info btn-sm">
        <i class="fas fa-check"></i> Assign Selected
      </button>
      <button type="submit" form="selection"
              formmethod="post"
              formaction="<?= e(route('admin.v1.access.role.permission.unassign_selected', ['id' => $role->id])) ?>"
              data-confirm="Confirm Unassign"
              class="btn btn-danger btn-sm">
        <i class="fas fa-times"></i> Unassign Selected
      </button>
    </div>
  </div>

  <div class="p-4" style="overflow-x:auto">
    <table class="table table-bordered table-hover align-middle">
      <thead>
        <form id="searchform" method="get" action="<?= e(route('admin.v1.access.role.permission', ['id' => $role->id])) ?>">
          <tr>
            <th width="2%"></th>
            <th width="7%">
              <select name="q_page_size" id="q_page_size" class="form-control">
                <option value="10"  <?= ($filter['q_page_size'] ?? '10') == '10'  ? 'selected' : '' ?>>10</option>
                <option value="20"  <?= ($filter['q_page_size'] ?? '') == '20'  ? 'selected' : '' ?>>20</option>
                <option value="50"  <?= ($filter['q_page_size'] ?? '') == '50'  ? 'selected' : '' ?>>50</option>
                <option value="100" <?= ($filter['q_page_size'] ?? '') == '100' ? 'selected' : '' ?>>100</option>
              </select>
            </th>
            <th width="20%"><input id="q_name" type="text" class="form-control" name="q_name" value="<?= e($filter['q_name'] ?? '') ?>"></th>
            <th width="10%">
              <select name="q_status" id="q_status" class="form-control">
                <option disabled <?= ($filter['q_status'] ?? '') === '' ? 'selected' : '' ?>>Select</option>
                <option value="Active"   <?= ($filter['q_status'] ?? '') === 'Active'   ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= ($filter['q_status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </th>
            <th width="15%"><input id="q_desc" type="text" class="form-control" name="q_desc" value="<?= e($filter['q_desc'] ?? '') ?>"></th>
            <th width="5%" class="text-center align-middle">
              <div class="btn-group">
                <button type="submit" form="searchform" class="btn btn-sm btn-success"><i class="fas fa-search"></i></button>
                <a href="<?= e(route('admin.v1.access.role.permission', ['id' => $role->id])) ?>" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></a>
              </div>
            </th>
          </tr>
          <tr>
            <th width="5%"><input type="checkbox" id="checkall" /></th>
            <th width="5%">No</th>
            <th width="20%">Name</th>
            <th width="15%">Status</th>
            <th width="10%">Description</th>
            <th width="5%">Action</th>
          </tr>
        </form>
      </thead>
      <tbody>
        <form id="selection" method="post" action="<?= e(route('admin.v1.access.role.permission.assign_selected', ['id' => $role->id])) ?>">
          <?= csrf_field() ?>
          <?php foreach ($paginate['datas'] as $i => $item) : ?>
          <tr>
            <td><input name="selected[]" value="<?= e($item->id) ?>" type="checkbox" /></td>
            <td><?= ($i + 1) + ($_pd['page_size'] * ($_pd['current_page'] - 1)) ?></td>
            <td><?= e($item->name) ?></td>
            <td class="text-left">
                <?php if (!empty($item->is_assigned)) : ?>
                <i class="fas fa-check-circle text-green-500 text-xl" title="Assigned"></i>
                <?php else : ?>
                <i class="fas fa-times-circle text-gray-300 text-xl" title="Not assigned"></i>
                <?php endif; ?>
            </td>
            <td><?= e((string)($item->desc ?? '')) ?></td>
            <td class="text-center">
              <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle-dd aria-expanded="false">Action</button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a href="<?= e(route('admin.v1.access.role.permission.assign', ['id' => $role->id, 'permission_id' => $item->id])) ?>" class="dropdown-item">
                    <i class="fas fa-check"></i> Assign
                  </a>
                  <div class="dropdown-divider"></div>
                  <a href="<?= e(route('admin.v1.access.role.permission.unassign', ['id' => $role->id, 'permission_id' => $item->id])) ?>" class="dropdown-item danger">
                    <i class="fas fa-times"></i> Unassign
                  </a>
                </div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </form>
      </tbody>
    </table>

    <div class="d-flex justify-content-end mt-4">
      <nav>
        <ul class="pagination">
          <?php if ($_pd['current_page'] > 1) : ?>
            <li class="page-item"><a class="page-link" href="<?= e($pageUrl($_pd['current_page'] - 1)) ?>">Previous</a></li>
          <?php endif; ?>
          <?php for ($p = 1; $p <= $_pd['total_page']; $p++) : ?>
            <li class="page-item <?= $p === $_pd['current_page'] ? 'active' : '' ?>">
              <a class="page-link" href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($_pd['current_page'] < $_pd['total_page'] && $_pd['total_page'] > 0) : ?>
            <li class="page-item"><a class="page-link" href="<?= e($pageUrl($_pd['current_page'] + 1)) ?>">Next</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </div>
</div>
