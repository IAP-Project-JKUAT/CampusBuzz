<?php
// public/profile.php
require_once '../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pageTitle = 'Profile';
$basePath = '../';
$currentUserId = (int) $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT username, email, bio, created_at FROM users WHERE user_id = ?");
$stmt->execute([$currentUserId]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Fetch user's posts with real like/comment counts and liked state
$stmt = $pdo->prepare("
    SELECT
        p.*,
        (SELECT COUNT(*) FROM post_likes pl  WHERE pl.post_id  = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id) AS comment_count,
        (SELECT COUNT(*) FROM post_likes pl2 WHERE pl2.post_id = p.post_id AND pl2.user_id = :uid) AS user_liked
    FROM posts p
    WHERE p.user_id = :owner
    ORDER BY p.created_at DESC
");
$stmt->bindValue(':uid', $currentUserId, PDO::PARAM_INT);
$stmt->bindValue(':owner', $currentUserId, PDO::PARAM_INT);
$stmt->execute();
$myPosts = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="mt-4">
    <div class="card" style="text-align: center; padding: 2rem;">
        <div class="avatar"
            style="width: 80px; height: 80px; margin: 0 auto 1rem; background-image: url('https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=random&size=128');">
        </div>
        <h1 id="profile-username-text" style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem;">
            <?php echo htmlspecialchars($user['username']); ?>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($user['email']); ?>
        </p>

        <div style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 1.5rem;">
            <div style="text-align: center;">
                <span
                    style="display: block; font-weight: 800; font-size: 1.125rem;"><?php echo count($myPosts); ?></span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Posts</span>
            </div>
            <div style="text-align: center;">
                <span
                    style="display: block; font-weight: 800; font-size: 1.125rem;"><?php echo rand(100, 500); ?></span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Followers</span>
            </div>
            <div style="text-align: center;">
                <span style="display: block; font-weight: 800; font-size: 1.125rem;"><?php echo rand(50, 200); ?></span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Following</span>
            </div>
        </div>

        <?php if (!empty($user['bio'])): ?>
            <p id="profile-bio-text"
                style="margin-bottom: 1.5rem; max-width: 400px; margin-left: auto; margin-right: auto;">
                <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
            </p>
        <?php else: ?>
            <p id="profile-bio-text" style="color: var(--text-muted); font-style: italic; margin-bottom: 1.5rem;">No bio
                yet.</p>
        <?php endif; ?>

        <button id="edit-profile-btn" class="btn btn-secondary btn-sm">Edit Profile</button>
    </div>

    <!-- Category Filter for Profile -->
    <div class="categories" style="margin-bottom: 1rem;">
        <button class="category-pill active">Posts</button>
    </div>

    <?php if (count($myPosts) > 0): ?>
        <div class="posts-feed" id="posts-feed">
            <?php foreach ($myPosts as $post):
                $userLiked = (int) $post['user_liked'] > 0;
                ?>
                <article class="card post-card" id="post-<?php echo $post['post_id']; ?>">
                    <div class="card-header">
                        <div class="post-header-user">
                            <div class="avatar"
                                style="background-image: url('https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=random');">
                            </div>
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                                <p><?php echo date('M j', strtotime($post['created_at'])); ?> • <span
                                        style="color: var(--primary-color);">Profile</span></p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <a href="../posts/edit.php?id=<?php echo $post['post_id']; ?>"
                                style="color: var(--text-muted); display:flex; align-items:center;" title="Edit post">
                                <span class="material-symbols-outlined" style="font-size:20px;">edit</span>
                            </a>
                            <button class="delete-btn" data-post-id="<?php echo $post['post_id']; ?>" title="Delete post"
                                aria-label="Delete post">
                                <span class="material-symbols-outlined" style="font-size:20px;">delete</span>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    </div>

                    <div class="card-footer">
                        <div class="post-actions">
                            <!-- Like Button -->
                            <button class="action-btn like-btn <?php echo $userLiked ? 'liked' : ''; ?>"
                                data-post-id="<?php echo $post['post_id']; ?>" aria-label="Like post">
                                <span class="material-symbols-outlined like-icon">favorite</span>
                                <span class="like-count"><?php echo $post['like_count']; ?></span>
                            </button>

                            <!-- Comment Button -->
                            <button class="action-btn comment-btn" data-post-id="<?php echo $post['post_id']; ?>"
                                aria-label="Comment on post">
                                <span class="material-symbols-outlined">chat_bubble</span>
                                <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- Comment Section -->
                    <div class="comment-section" id="comments-<?php echo $post['post_id']; ?>" data-loaded="false">
                        <div class="comments-list" id="comments-list-<?php echo $post['post_id']; ?>">
                            <div class="comments-loading">
                                <span class="material-symbols-outlined spin">progress_activity</span>
                            </div>
                        </div>
                        <div class="comment-input-row">
                            <div class="avatar comment-avatar"
                                style="background-image: url('https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=random'); width:2rem; height:2rem; flex-shrink:0;">
                            </div>
                            <input type="text" class="comment-input" placeholder="Write a comment…" maxlength="280"
                                data-post-id="<?php echo $post['post_id']; ?>" aria-label="Comment text">
                            <button class="comment-submit-btn" data-post-id="<?php echo $post['post_id']; ?>"
                                aria-label="Send comment">
                                <span class="material-symbols-outlined">send</span>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
            <span class="material-symbols-outlined"
                style="font-size: 48px; margin-bottom: 1rem; opacity: 0.5;">post_add</span>
            <p>You haven't posted anything yet.</p>
            <a href="../posts/create.php" class="btn btn-primary mt-4">Create your first post</a>
        </div>
    <?php endif; ?>
</div>

</main>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title"
    aria-hidden="true">
    <div class="modal-box">
        <span class="material-symbols-outlined modal-icon">delete_forever</span>
        <h2 id="delete-modal-title">Delete Post?</h2>
        <p>This action cannot be undone.</p>
        <div class="modal-actions">
            <button id="modal-cancel" class="btn btn-secondary">Cancel</button>
            <button id="modal-confirm" class="btn btn-danger-solid">Delete</button>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-profile-title"
    aria-hidden="true">
    <div class="modal-box" style="text-align: left;">
        <h2 id="edit-profile-title" style="margin-top: 0; margin-bottom: 1rem;">Edit Profile</h2>
        <div id="edit-profile-error" class="alert alert-danger" style="display: none; margin-bottom: 1rem;"></div>
        <form id="edit-profile-form">
            <div class="form-group">
                <label for="edit-username">Username</label>
                <input type="text" id="edit-username" name="username" required
                    value="<?php echo htmlspecialchars($user['username']); ?>"
                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-color); color: var(--text-color); font-family: inherit;">
            </div>
            <div class="form-group" style="margin-top: 1rem;">
                <label for="edit-bio">Bio</label>
                <textarea id="edit-bio" name="bio" rows="4" placeholder="Tell us about yourself..."
                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-color); color: var(--text-color); font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>
            <div class="modal-actions" style="margin-top: 1.5rem;">
                <button type="button" id="edit-modal-cancel" class="btn btn-secondary">Cancel</button>
                <button type="submit" id="edit-modal-save" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const BASE = '../posts/';
        let pendingDeletePostId = null;

        /* ─── LIKE ───────────────────────────────────────────────────── */
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const postId = btn.dataset.postId;
                const countEl = btn.querySelector('.like-count');
                const wasLiked = btn.classList.contains('liked');
                const delta = wasLiked ? -1 : 1;

                btn.classList.toggle('liked');
                countEl.textContent = Math.max(0, parseInt(countEl.textContent) + delta);

                try {
                    const res = await fetch(BASE + 'like.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ post_id: parseInt(postId) })
                    });
                    const data = await res.json();
                    if (data.error) throw new Error(data.error);
                    countEl.textContent = data.count;
                    btn.classList.toggle('liked', data.liked);
                } catch {
                    btn.classList.toggle('liked', wasLiked);
                    countEl.textContent = Math.max(0, parseInt(countEl.textContent) - delta);
                }
            });
        });

        /* ─── COMMENTS ───────────────────────────────────────────────── */
        document.querySelectorAll('.comment-btn').forEach(btn => {
            btn.addEventListener('click', () => toggleComments(btn.dataset.postId));
        });

        async function toggleComments(postId) {
            const section = document.getElementById('comments-' + postId);
            const isOpen = section.classList.contains('open');
            if (isOpen) { section.classList.remove('open'); return; }
            section.classList.add('open');
            if (section.dataset.loaded === 'true') return;

            try {
                const res = await fetch(BASE + 'get_comments.php?post_id=' + postId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                renderComments(postId, data.comments || []);
                section.dataset.loaded = 'true';
            } catch {
                document.getElementById('comments-list-' + postId).innerHTML =
                    '<p class="comment-error">Failed to load comments.</p>';
            }
        }

        function renderComments(postId, comments) {
            const list = document.getElementById('comments-list-' + postId);
            list.innerHTML = comments.length
                ? comments.map(c => commentHTML(c.username, c.comment_text, c.time)).join('')
                : '<p class="no-comments">No comments yet. Be the first!</p>';
        }

        function commentHTML(username, text, time) {
            const initials = encodeURIComponent(username);
            return `<div class="comment-item">
            <div class="avatar" style="width:2rem;height:2rem;flex-shrink:0;background-image:url('https://ui-avatars.com/api/?name=${initials}&background=random');"></div>
            <div class="comment-bubble">
                <span class="comment-username">${escapeHtml(username)}</span>
                <span class="comment-text">${escapeHtml(text)}</span>
                <span class="comment-time">${escapeHtml(time)}</span>
            </div>
        </div>`;
        }

        function escapeHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str));
            return d.innerHTML;
        }

        document.querySelectorAll('.comment-submit-btn').forEach(btn => {
            btn.addEventListener('click', () => submitComment(btn.dataset.postId));
        });
        document.querySelectorAll('.comment-input').forEach(input => {
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submitComment(input.dataset.postId); }
            });
        });

        async function submitComment(postId) {
            const input = document.querySelector(`.comment-input[data-post-id="${postId}"]`);
            const submitBtn = document.querySelector(`.comment-submit-btn[data-post-id="${postId}"]`);
            const countEl = document.querySelector(`#post-${postId} .comment-count`);
            const text = input.value.trim();
            if (!text) return;

            input.disabled = submitBtn.disabled = true;
            try {
                const res = await fetch(BASE + 'comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ post_id: parseInt(postId), comment_text: text })
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                const list = document.getElementById('comments-list-' + postId);
                const section = document.getElementById('comments-' + postId);
                const emptyMsg = list.querySelector('.no-comments, .comments-loading');
                if (emptyMsg) emptyMsg.remove();

                list.insertAdjacentHTML('beforeend', commentHTML(data.comment.username, data.comment.text, data.comment.created_at));
                list.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                section.dataset.loaded = 'true';
                countEl.textContent = parseInt(countEl.textContent) + 1;
                input.value = '';
            } catch {
                alert('Failed to post comment. Please try again.');
            } finally {
                input.disabled = submitBtn.disabled = false;
                input.focus();
            }
        }

        /* ─── DELETE ─────────────────────────────────────────────────── */
        const modal = document.getElementById('delete-modal');
        const modalCancel = document.getElementById('modal-cancel');
        const modalConfirm = document.getElementById('modal-confirm');

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                pendingDeletePostId = btn.dataset.postId;
                modal.classList.add('visible');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        modalCancel.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        function closeModal() {
            modal.classList.remove('visible');
            modal.setAttribute('aria-hidden', 'true');
            pendingDeletePostId = null;
        }

        modalConfirm.addEventListener('click', async () => {
            if (!pendingDeletePostId) return;
            const postId = pendingDeletePostId;
            const card = document.getElementById('post-' + postId);
            closeModal();

            try {
                const res = await fetch(BASE + 'delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ post_id: parseInt(postId) })
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                card.classList.add('post-deleting');
                card.addEventListener('transitionend', () => card.remove(), { once: true });
            } catch {
                alert('Failed to delete post. Please try again.');
            }
        });

        /* ─── EDIT PROFILE ───────────────────────────────────────────── */
        const editModal = document.getElementById('edit-profile-modal');
        const editBtn = document.getElementById('edit-profile-btn');
        const editCancel = document.getElementById('edit-modal-cancel');
        const editForm = document.getElementById('edit-profile-form');
        const editError = document.getElementById('edit-profile-error');

        if (editBtn) {
            editBtn.addEventListener('click', () => {
                editError.style.display = 'none';
                editModal.classList.add('visible');
                editModal.setAttribute('aria-hidden', 'false');
            });
        }

        function closeEditModal() {
            editModal.classList.remove('visible');
            editModal.setAttribute('aria-hidden', 'true');
        }

        if (editCancel) editCancel.addEventListener('click', closeEditModal);
        editModal.addEventListener('click', e => { if (e.target === editModal) closeEditModal(); });

        if (editForm) {
            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = document.getElementById('edit-modal-save');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                editError.style.display = 'none';

                const formData = new FormData(editForm);
                const data = {
                    username: formData.get('username'),
                    bio: formData.get('bio')
                };

                try {
                    const res = await fetch('update_profile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await res.json();

                    if (result.error) {
                        throw new Error(result.error);
                    }

                    // Update UI dynamically
                    document.getElementById('profile-username-text').textContent = result.user.username;

                    const bioEl = document.getElementById('profile-bio-text');
                    if (result.user.bio) {
                        bioEl.innerHTML = result.user.bio.replace(/\n/g, '<br>');
                        bioEl.style = "margin-bottom: 1.5rem; max-width: 400px; margin-left: auto; margin-right: auto;";
                    } else {
                        bioEl.textContent = 'No bio yet.';
                        bioEl.style = "color: var(--text-muted); font-style: italic; margin-bottom: 1.5rem;";
                    }

                    // Update main avatar URL (with size=128)
                    const newAvatarUrl = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(result.user.username) + '&background=random&size=128';
                    const mainAvatar = document.querySelector('.card .avatar');
                    if (mainAvatar) {
                        mainAvatar.style.backgroundImage = `url('${newAvatarUrl}')`;
                    }

                    // Update post header avatars and usernames
                    const newSmallAvatarUrl = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(result.user.username) + '&background=random';
                    document.querySelectorAll('.post-header-user .avatar').forEach(av => {
                        av.style.backgroundImage = `url('${newSmallAvatarUrl}')`;
                    });
                    document.querySelectorAll('.post-header-user h3').forEach(h3 => {
                        h3.textContent = result.user.username;
                    });

                    closeEditModal();

                    // Show a quick success message (optional but nice)
                    const tempMsg = document.createElement('div');
                    tempMsg.className = 'alert alert-success';
                    tempMsg.style = 'position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
                    tempMsg.textContent = 'Profile updated successfully!';
                    document.body.appendChild(tempMsg);
                    setTimeout(() => tempMsg.remove(), 3000);

                } catch (err) {
                    editError.textContent = err.message;
                    editError.style.display = 'block';
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }
    })();
</script>

</body>

</html>