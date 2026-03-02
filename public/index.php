<?php
// public/index.php
require_once '../includes/db.php';

// Auth check
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pageTitle = 'Feed';
$basePath = '../';
$currentUserId = (int) $_SESSION['user_id'];

// Pagination
$postsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $postsPerPage;

// Get total posts
$stmt = $pdo->query("SELECT COUNT(*) FROM posts");
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $postsPerPage);

// Fetch posts with real like counts, comment counts, and whether current user liked each
$stmt = $pdo->prepare("
    SELECT
        p.*,
        u.username,
        u.email,
        (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id) AS comment_count,
        (SELECT COUNT(*) FROM post_likes pl2 WHERE pl2.post_id = p.post_id AND pl2.user_id = :uid) AS user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.user_id
    ORDER BY p.created_at DESC
    LIMIT :lim OFFSET :off
");
$stmt->bindValue(':uid', $currentUserId, PDO::PARAM_INT);
$stmt->bindValue(':lim', $postsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- Feed Categories -->
<div class="categories">
    <button class="category-pill active">For You</button>
    <button class="category-pill">Following</button>
    <button class="category-pill">Clubs</button>
    <button class="category-pill">Events</button>
    <button class="category-pill">Marketplace</button>
</div>

<div class="mt-4">
    <?php if (count($posts) > 0): ?>
        <div class="posts-feed" id="posts-feed">
            <?php foreach ($posts as $post):
                $isOwner = ($post['user_id'] == $currentUserId);
                $userLiked = (int) $post['user_liked'] > 0;
                ?>
                <article class="card post-card" id="post-<?php echo $post['post_id']; ?>">
                    <div class="card-header">
                        <div class="post-header-user">
                            <div class="avatar"
                                style="background-image: url('https://ui-avatars.com/api/?name=<?php echo urlencode($post['username']); ?>&background=random');">
                            </div>
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                                <p>
                                    <?php
                                    $time = strtotime($post['created_at']);
                                    echo date('g:i a', $time) . ' • <span style="color: var(--primary-color);">Campus</span>';
                                    ?>
                                </p>
                            </div>
                        </div>

                        <!-- Post actions: edit + delete for own posts, ellipsis for others -->
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <?php if ($isOwner): ?>
                                <a href="../posts/edit.php?id=<?php echo $post['post_id']; ?>"
                                    style="color: var(--text-muted); display:flex; align-items:center;" title="Edit post">
                                    <span class="material-symbols-outlined" style="font-size:20px;">edit</span>
                                </a>
                                <button class="delete-btn" data-post-id="<?php echo $post['post_id']; ?>" title="Delete post"
                                    aria-label="Delete post">
                                    <span class="material-symbols-outlined" style="font-size:20px;">delete</span>
                                </button>
                            <?php else: ?>
                                <button
                                    style="background:none; border:none; color: var(--text-muted); cursor:pointer; display:flex; align-items:center;">
                                    <span class="material-symbols-outlined">more_horiz</span>
                                </button>
                            <?php endif; ?>
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

                        <button class="action-btn" aria-label="Share post">
                            <span class="material-symbols-outlined">share</span>
                        </button>
                    </div>

                    <!-- Comment Section (collapsed by default) -->
                    <div class="comment-section" id="comments-<?php echo $post['post_id']; ?>" data-loaded="false">
                        <div class="comments-list" id="comments-list-<?php echo $post['post_id']; ?>">
                            <div class="comments-loading">
                                <span class="material-symbols-outlined spin">progress_activity</span>
                            </div>
                        </div>
                        <div class="comment-input-row">
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

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">&laquo; Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <p style="text-align: center; color: var(--text-muted); margin-top: 2rem;">No posts yet. Be the first to buzz!</p>
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

<script>
    (function () {
        const BASE = '../posts/';
        let pendingDeletePostId = null;

        /* ─── LIKE ──────────────────────────────────────────────────── */
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const postId = btn.dataset.postId;
                const countEl = btn.querySelector('.like-count');
                const iconEl = btn.querySelector('.like-icon');
                const wasLiked = btn.classList.contains('liked');

                // Optimistic UI update
                btn.classList.toggle('liked');
                const delta = wasLiked ? -1 : 1;
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
                    // Ensure class matches server state
                    btn.classList.toggle('liked', data.liked);
                } catch (e) {
                    // Rollback on failure
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

            if (isOpen) {
                section.classList.remove('open');
                return;
            }

            section.classList.add('open');

            // Load comments only once
            if (section.dataset.loaded === 'true') return;

            try {
                const res = await fetch(BASE + 'get_comments.php?post_id=' + postId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                renderComments(postId, data.comments || []);
                section.dataset.loaded = 'true';
            } catch (e) {
                document.getElementById('comments-list-' + postId).innerHTML =
                    '<p class="comment-error">Failed to load comments.</p>';
            }
        }

        function renderComments(postId, comments) {
            const list = document.getElementById('comments-list-' + postId);
            if (!comments.length) {
                list.innerHTML = '<p class="no-comments">No comments yet. Be the first!</p>';
                return;
            }
            list.innerHTML = comments.map(c => commentHTML(c.username, c.comment_text, c.time)).join('');
        }

        function commentHTML(username, text, time) {
            const initials = encodeURIComponent(username);
            return `
            <div class="comment-item">
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

        // Submit comment
        document.querySelectorAll('.comment-submit-btn').forEach(btn => {
            btn.addEventListener('click', () => submitComment(btn.dataset.postId));
        });

        document.querySelectorAll('.comment-input').forEach(input => {
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    submitComment(input.dataset.postId);
                }
            });
        });

        async function submitComment(postId) {
            const input = document.querySelector(`.comment-input[data-post-id="${postId}"]`);
            const text = input.value.trim();
            const submitBtn = document.querySelector(`.comment-submit-btn[data-post-id="${postId}"]`);
            const countEl = document.querySelector(`#post-${postId} .comment-count`);

            if (!text) return;

            input.disabled = true;
            submitBtn.disabled = true;

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

                // Remove empty state message if present
                const emptyMsg = list.querySelector('.no-comments, .comments-loading');
                if (emptyMsg) emptyMsg.remove();

                // Append new comment
                list.insertAdjacentHTML('beforeend', commentHTML(data.comment.username, data.comment.text, data.comment.created_at));
                list.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Mark as loaded
                section.dataset.loaded = 'true';

                // Update count
                countEl.textContent = parseInt(countEl.textContent) + 1;
                input.value = '';
            } catch (e) {
                alert('Failed to post comment. Please try again.');
            } finally {
                input.disabled = false;
                submitBtn.disabled = false;
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

                // Fade-out and remove the card
                card.classList.add('post-deleting');
                card.addEventListener('transitionend', () => card.remove(), { once: true });
            } catch (e) {
                alert('Failed to delete post. Please try again.');
            }
        });
    })();
</script>

</body>

</html>