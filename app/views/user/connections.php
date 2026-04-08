<?php
$profileUser = is_array($profile_user ?? null) ? $profile_user : [];
$items = is_array($items ?? null) ? $items : [];
$followers = is_array($followers ?? null) ? $followers : [];
$following = is_array($following ?? null) ? $following : [];
$type = ($type ?? 'followers') === 'following' ? 'following' : 'followers';
$viewerId = (int) ($viewer_id ?? 0);
$isLoggedIn = (bool) ($is_logged_in ?? false);
$isOwner = (bool) ($is_owner ?? false);
$profileUserId = (int) ($profileUser['id'] ?? 0);

$name = trim((string) ($profileUser['name'] ?? 'Người dùng'));
if ($name === '') {
    $name = 'Người dùng';
}

$defaultGroup = $type === 'followers' ? 'followers' : 'following';
$activeGroup = (string) ($_GET['group'] ?? $defaultGroup);
if (!in_array($activeGroup, ['following', 'followers', 'friends'], true)) {
    $activeGroup = $defaultGroup;
}

$scopeText = $type === 'followers'
    ? ($isOwner ? 'Danh sách người theo dõi bạn' : 'Danh sách người theo dõi tài khoản này')
    : ($isOwner ? 'Danh sách bạn đang theo dõi' : 'Danh sách tài khoản này đang theo dõi');

$filteredItems = [];
$followersList = [];
$followingList = [];
$friendsList = [];

$cleanList = static function (array $list, int $viewerId, bool $isOwner): array {
    $result = [];
    foreach ($list as $item) {
        $itemId = (int) ($item['id'] ?? 0);
        if ($isOwner && $viewerId > 0 && $viewerId === $itemId) {
            continue;
        }
        $result[] = $item;
    }
    return $result;
};

$followersList = $cleanList($followers, $viewerId, $isOwner);
$followingList = $cleanList($following, $viewerId, $isOwner);

$followersMap = [];
foreach ($followersList as $item) {
    $followersMap[(int) ($item['id'] ?? 0)] = $item;
}

$friendsMap = [];
foreach ($followingList as $item) {
    $itemId = (int) ($item['id'] ?? 0);
    if ($itemId > 0 && isset($followersMap[$itemId])) {
        $friendsMap[$itemId] = $item;
    }
}

$friendsList = array_values($friendsMap);

$filteredItems = $type === 'followers' ? $followersList : $followingList;

$currentListPath = '/users/' . $profileUserId . '/' . $type;

$renderItem = static function (array $item) use ($viewerId, $isLoggedIn, $currentListPath, $isOwner, $activeGroup): void {
    $itemId = (int) ($item['id'] ?? 0);

    $itemName = trim((string) ($item['name'] ?? 'Người dùng'));
    if ($itemName === '') {
        $itemName = 'Người dùng';
    }

    $itemUsername = trim((string) ($item['username'] ?? ''));
    $itemAvatar = trim((string) ($item['avatar'] ?? ''));

    $avatarUrl = $itemAvatar !== ''
        ? URLROOT . '/uploads/' . rawurlencode($itemAvatar)
        : '';

    $isSelf = $viewerId > 0 && $viewerId === $itemId;

    $followsViewer = (int) ($item['follows_viewer'] ?? 0) === 1;
    $isFollowingByViewer = (int) ($item['is_following_by_viewer'] ?? 0) === 1;

    $isFriend = $followsViewer && $isFollowingByViewer;

    $subtitle = $itemUsername !== '' ? ('@' . $itemUsername) : '@thanhvien';
?>
<article class="flex items-center justify-between gap-3 border-b border-slate-100 py-3 js-connection-item"
data-item-id="<?= $itemId; ?>"
data-follows-viewer="<?= $followsViewer ? '1' : '0'; ?>"
data-following-by-viewer="<?= $isFollowingByViewer ? '1' : '0'; ?>">

<a class="flex min-w-0 items-center gap-3" href="<?= URLROOT; ?>/users/<?= $itemId; ?>">

<?php if ($avatarUrl !== ''): ?>
<img class="h-11 w-11 rounded-full object-cover"
src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>">
<?php else: ?>

<div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-700">
<?= htmlspecialchars(strtoupper(substr($itemName,0,1)), ENT_QUOTES,'UTF-8'); ?>
</div>

<?php endif; ?>

<div class="min-w-0">

<p class="truncate text-sm font-semibold text-slate-900">
<?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'); ?>
</p>

<p class="truncate text-xs text-slate-500">
<?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?>
</p>

<?php if ($followsViewer && !$isFriend): ?>
<p class="text-[11px] font-semibold text-amber-600">Theo dõi lại</p>
<?php endif; ?>

</div>
</a>

<div class="flex items-center gap-2">

<?php if ($isSelf): ?>

<span class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500">
Bạn
</span>

<?php elseif (!$isLoggedIn): ?>

<a class="rounded-md bg-black px-3 py-1.5 text-xs font-semibold text-white"
href="<?= URLROOT; ?>/login">
Đăng nhập
</a>

<?php elseif ($isFriend): ?>

<form method="post" action="<?= URLROOT; ?>/users/<?= $itemId; ?>/unfollow">
    <?= csrf_field(); ?>
    <input type="hidden" name="redirect_to"
    value="<?= htmlspecialchars($currentListPath, ENT_QUOTES, 'UTF-8'); ?>">
    <button class="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100" type="submit">
        Bạn be
    </button>
</form>

<?php elseif ($isFollowingByViewer): ?>

<form method="post"
    <?= csrf_field(); ?>
action="<?= URLROOT; ?>/users/<?= $itemId; ?>/unfollow"
class="js-follow-form"
data-follow-action="<?= URLROOT; ?>/users/<?= $itemId; ?>/follow"
data-unfollow-action="<?= URLROOT; ?>/users/<?= $itemId; ?>/unfollow">

<input type="hidden" name="redirect_to"
value="<?= htmlspecialchars($currentListPath, ENT_QUOTES, 'UTF-8'); ?>">

<button class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 js-follow-btn"
data-state="following"
data-text-following="Đang theo dõi"
data-text-follow="Theo dõi lại"
data-class-following="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
data-class-follow="rounded-md bg-black px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
Đang theo dõi
</button>

</form>

<?php else: ?>

<form method="post"
    <?= csrf_field(); ?>
action="<?= URLROOT; ?>/users/<?= $itemId; ?>/follow"
class="js-follow-form"
data-follow-action="<?= URLROOT; ?>/users/<?= $itemId; ?>/follow"
data-unfollow-action="<?= URLROOT; ?>/users/<?= $itemId; ?>/unfollow">

<input type="hidden" name="redirect_to"
value="<?= htmlspecialchars($currentListPath, ENT_QUOTES, 'UTF-8'); ?>">

<button class="rounded-md bg-black px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 js-follow-btn"
data-state="follow"
data-text-following="Đang theo dõi"
data-text-follow="Theo dõi lại"
data-class-following="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
data-class-follow="rounded-md bg-black px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">

<?= $followsViewer ? 'Theo dõi lại' : 'Theo dõi'; ?>

</button>

</form>

<?php endif; ?>

<?php if ($isOwner && $activeGroup === 'followers' && !$isSelf && $followsViewer): ?>
<form method="post"
    <?= csrf_field(); ?>
action="<?= URLROOT; ?>/users/<?= $itemId; ?>/remove-follower"
class="js-remove-follower"
onsubmit="return confirm('Bạn có muốn loại bỏ theo dõi của người này không?');">
    <input type="hidden" name="redirect_to"
    value="<?= htmlspecialchars($currentListPath, ENT_QUOTES, 'UTF-8'); ?>">
    <button class="rounded-md border border-rose-300 px-2 py-1.5 text-[11px] font-semibold text-rose-600 hover:bg-rose-50" type="submit">
        Xóa
    </button>
</form>
<?php endif; ?>

</div>

</article>
<?php
};
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 js-connections"
    data-is-owner="<?= $isOwner ? '1' : '0'; ?>">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="text-xs font-semibold text-primary"><?= htmlspecialchars($scopeText, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <a class="text-xs font-semibold text-slate-500 hover:text-slate-900" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>">Đóng</a>
        </div>

        <div class="mb-4 flex flex-wrap gap-2 text-xs font-semibold">
            <a class="rounded-full px-3 py-1.5 js-tab-count <?= $activeGroup === 'following' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>" data-key="following" data-count="<?= count($followingList); ?>" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/<?= $type; ?>?group=following">Bạn theo dõi (<span class="js-count"><?= count($followingList); ?></span>)</a>
            <a class="rounded-full px-3 py-1.5 js-tab-count <?= $activeGroup === 'followers' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>" data-key="followers" data-count="<?= count($followersList); ?>" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/<?= $type; ?>?group=followers">Theo dõi bạn (<span class="js-count"><?= count($followersList); ?></span>)</a>
            <a class="rounded-full px-3 py-1.5 js-tab-count <?= $activeGroup === 'friends' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>" data-key="friends" data-count="<?= count($friendsList); ?>" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/<?= $type; ?>?group=friends">Bạn be (<span class="js-count"><?= count($friendsList); ?></span>)</a>
        </div>

        <?php
        $activeList = match ($activeGroup) {
            'followers' => $followersList,
            'friends' => $friendsList,
            default => $followingList,
        };

        $emptyText = match ($activeGroup) {
            'followers' => 'Chưa có người theo dõi trong danh sách này.',
            'friends' => 'Chưa có bạn bè.',
            default => 'Bạn chưa theo dõi ai trong danh sách này.',
        };
        ?>

        <?php if (empty($activeList)): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500"><?= htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php else: ?>
            <div>
<?php
foreach ($activeList as $item) { $renderItem($item); }
?>
            </div>
<?php endif; ?>
    </div>
</section>

<script>
function updateCount(key, delta) {
  const tab = document.querySelector(`.js-tab-count[data-key="${key}"]`);
  if (!tab) return;
  const countEl = tab.querySelector('.js-count');
  const current = Number(tab.dataset.count || 0);
  const next = Math.max(0, current + delta);
  tab.dataset.count = String(next);
  if (countEl) {
    countEl.textContent = String(next);
  }
}

document.addEventListener('click', function (event) {
  const button = event.target.closest('.js-follow-btn');
  if (!button) return;

  const form = button.closest('.js-follow-form');
  if (!form) return;

  const container = document.querySelector('.js-connections');
  const isOwner = container && container.dataset.isOwner === '1';
  const item = button.closest('.js-connection-item');
  const followsViewer = item && item.dataset.followsViewer === '1';
  const isFollowingByViewer = item && item.dataset.followingByViewer === '1';

  event.preventDefault();

  const state = button.dataset.state || 'follow';
  const nextAction = state === 'following'
    ? form.dataset.unfollowAction
    : form.dataset.followAction;

  const body = new URLSearchParams(new FormData(form));

  fetch(nextAction, {
    method: 'POST',
    body,
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'fetch' }
  }).then(function () {
    const nextState = state === 'following' ? 'follow' : 'following';
    const nextText = nextState === 'following'
      ? (button.dataset.textFollowing || 'Đang theo dõi')
      : (button.dataset.textFollow || 'Theo dõi lại');

    const nextClass = nextState === 'following'
      ? (button.dataset.classFollowing || '')
      : (button.dataset.classFollow || '');

    button.dataset.state = nextState;
    button.textContent = nextText;
    if (nextClass) {
      button.className = nextClass + ' js-follow-btn';
    }

    form.setAttribute('action', nextState === 'following'
      ? form.dataset.unfollowAction
      : form.dataset.followAction);

    if (item) {
      if (nextState === 'following') {
        item.dataset.followingByViewer = '1';
      } else {
        item.dataset.followingByViewer = '0';
      }
    }

    if (isOwner) {
      if (state === 'following' && nextState === 'follow') {
        updateCount('following', -1);
        if (followsViewer && isFollowingByViewer) {
          updateCount('friends', -1);
        }
      } else if (state === 'follow' && nextState === 'following') {
        updateCount('following', 1);
        if (followsViewer) {
          updateCount('friends', 1);
        }
      }
    }
  });
});

document.addEventListener('submit', function (event) {
  const form = event.target.closest('.js-remove-follower');
  if (!form) return;

  const container = document.querySelector('.js-connections');
  const isOwner = container && container.dataset.isOwner === '1';
  const item = form.closest('.js-connection-item');
  const followsViewer = item && item.dataset.followsViewer === '1';
  const isFollowingByViewer = item && item.dataset.followingByViewer === '1';

  event.preventDefault();

  const body = new URLSearchParams(new FormData(form));
  fetch(form.getAttribute('action'), {
    method: 'POST',
    body,
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'fetch' }
  }).then(function () {
    if (item) {
      item.dataset.followsViewer = '0';
      if (container && container.dataset.isOwner === '1') {
        updateCount('followers', -1);
        if (isFollowingByViewer && followsViewer) {
          updateCount('friends', -1);
        }
      }
      item.remove();
    }
  });
});
</script>
