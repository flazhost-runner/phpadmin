<?php

/**
 * User index view.
 *
 * Expected variables (injected via extract):
 *   $filter   array<string,string>   active filter values
 *   $paginate array{items,total,page,per_page,last_page}
 *   $roles    list<Role>             all roles for filter dropdown
 *   $_csrf    string                 CSRF token
 */

declare(strict_types=1);

/** @var array<string,string> $filter */
/** @var array{datas:list<\PHPAdmin\Modules\Access\Models\User>,paginate_data:array{total_data:int,page_size:int,current_page:int,total_page:int}} $paginate */
/** @var list<\PHPAdmin\Modules\Access\Models\Role> $roles */
/** @var string $_csrf */

$_pd = $paginate['paginate_data'];

// Build pagination URL closure (preserves current filters, updates q_page)
$pageUrl = static function (int $p) use ($filter): string {
    $params = array_filter(
        array_merge($filter, ['q_page' => (string)$p]),
        static fn($v) => $v !== '' && $v !== null
    );
    return '?' . http_build_query($params);
};
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
</div>

<div class="tw-card p-0 overflow-hidden">
  <div class="px-6 py-4 border-b flex items-center justify-between">
    <h2 class="text-lg font-bold" style="color:var(--primary)">User List</h2>
    <div class="btn-group btn-sm">
      <a href="<?= e(route('admin.v1.access.user.create')) ?>" class="btn btn-success btn-sm">
        <i class="fas fa-fw fa-plus"></i> Add Data
      </a>
      <button type="submit" form="selection"
              formmethod="post"
              formaction="<?= e(route('admin.v1.access.user.delete_selected')) ?>"
              data-confirm="Confirm Delete"
              class="btn btn-danger btn-sm">
        <i class="fas fa-fw fa-times"></i> Delete Selected
      </button>
    </div>
  </div>

  <div class="p-4" style="overflow-x:auto">
    <table class="table table-bordered table-hover align-middle">
      <thead>
        <form id="searchform" method="get" action="<?= e(route('admin.v1.access.user.index')) ?>">
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
            <th width="15%"><input id="q_code"  type="text" class="form-control" name="q_code"  value="<?= e($filter['q_code']  ?? '') ?>"></th>
            <th width="20%"><input id="q_name"  type="text" class="form-control" name="q_name"  value="<?= e($filter['q_name']  ?? '') ?>"></th>
            <th width="15%"><input id="q_phone" type="text" class="form-control" name="q_phone" value="<?= e($filter['q_phone'] ?? '') ?>"></th>
            <th width="15%"><input id="q_email" type="text" class="form-control" name="q_email" value="<?= e($filter['q_email'] ?? '') ?>"></th>
            <th width="10%">
              <select name="q_status" id="q_status" class="form-control">
                <option disabled <?= ($filter['q_status'] ?? '') === '' ? 'selected' : '' ?>>Select</option>
                <option value="Active"   <?= ($filter['q_status'] ?? '') === 'Active'   ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= ($filter['q_status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </th>
            <th></th>
            <th width="12%">
              <select name="q_role" id="q_role" class="form-control">
                <option disabled <?= ($filter['q_role'] ?? '') === '' ? 'selected' : '' ?>>Select</option>
                <?php foreach ($roles as $role) : ?>
                  <option value="<?= e($role->id) ?>" <?= ($filter['q_role'] ?? '') === (string)$role->id ? 'selected' : '' ?>>
                    <?= e($role->name) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </th>
            <th width="5%" class="text-center align-middle">
              <div class="btn-group">
                <button type="submit" form="searchform" class="btn btn-sm btn-success"><i class="fas fa-fw fa-search"></i></button>
                <a href="<?= e(route('admin.v1.access.user.index')) ?>" class="btn btn-sm btn-danger"><i class="fas fa-fw fa-times"></i></a>
              </div>
            </th>
          </tr>
          <tr>
            <th width="5%"><input type="checkbox" id="checkall" /></th>
            <th width="5%">No</th>
            <th width="15%">Code</th>
            <th width="20%">Name</th>
            <th width="20%">Phone</th>
            <th width="15%">Email</th>
            <th width="15%">Status</th>
            <th width="15%">Picture</th>
            <th width="10%">Roles</th>
            <th width="5%">Action</th>
          </tr>
        </form>
      </thead>
      <tbody>
        <form id="selection" method="post" action="<?= e(route('admin.v1.access.user.delete_selected')) ?>">
          <?= csrf_field() ?>
          <?php foreach ($paginate['datas'] as $i => $item) : ?>
          <tr>
            <td><input name="selected[]" value="<?= e($item->id) ?>" type="checkbox" /></td>
            <td><?= ($i + 1) + ($_pd['page_size'] * ($_pd['current_page'] - 1)) ?></td>
            <td><?= e($item->code) ?></td>
            <td><?= e($item->name) ?></td>
            <td><?= e((string)($item->phone ?? '')) ?></td>
            <td><?= e($item->email) ?></td>
            <td class="text-left">
                <?php if ($item->status === 'Active') : ?>
                <i class="fas fa-check-circle text-green-500 text-xl" title="Active"></i>
                <?php else : ?>
                <i class="fas fa-times-circle text-red-500 text-xl" title="Inactive"></i>
                <?php endif; ?>
            </td>
            <td class="text-center">
              <img src="/<?= e((string)($item->picture ?? 'img/user-placeholder.png')) ?>" style="max-width:100px" alt="Picture">
            </td>
            <td>
                <?php foreach ($item->roles as $role) : ?>
                <span class="badge text-bg-primary"><?= e($role->name) ?></span>
                <?php endforeach; ?>
            </td>
            <td class="text-center">
              <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle-dd aria-expanded="false">Action</button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a href="<?= e(route('admin.v1.access.user.edit', ['id' => $item->id])) ?>" class="dropdown-item">
                    <i class="fas fa-pen fa-fw"></i> Edit
                  </a>
                  <div class="dropdown-divider"></div>
                  <?php
                    $deleteUrl = route('admin.v1.access.user.delete', ['id' => $item->id])
                      . '?_method=DELETE&_csrf=' . urlencode($_csrf ?? '');
                    ?>
                  <form method="post" action="<?= e($deleteUrl) ?>" class="m-0">
                    <button type="submit" data-confirm="Confirm Delete" class="dropdown-item danger">
                      <i class="fas fa-trash fa-fw"></i> Delete
                    </button>
                  </form>
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
