<div class="flex-between" style="margin-bottom: 24px;">
    <p style="color: var(--md-sys-color-on-surface-variant); margin: 0;"><?= __('roles_chart_desc') ?></p>
    <a href="/admin/roles/create" class="btn btn-filled">
        <span class="material-symbols-outlined">add</span>
        <?= __('new_role') ?>
    </a>
</div>

<style>
    /* Hierarchical Tree CSS */
    .tree-container {
        padding: 32px;
        background: white;
        border-radius: 16px;
    }
    .tree-container ul {
        padding-top: 20px; 
        position: relative;
        transition: all 0.5s;
        list-style: none;
        padding-left: 0;
    }
    .tree-container li {
        float: left; 
        text-align: center;
        list-style-type: none;
        position: relative;
        padding: 20px 5px 0 20px;
        transition: all 0.5s;
    }

    /* Connections */
    .tree-container li::before, .tree-container li::after {
        content: '';
        position: absolute; 
        top: 0; 
        right: 50%;
        border-top: 1px solid var(--md-sys-color-outline);
        width: 50%; 
        height: 20px;
    }
    .tree-container li::after {
        right: auto; 
        left: 50%;
        border-left: 1px solid var(--md-sys-color-outline);
    }

    /* Remove connectors for single child / first & last */
    .tree-container li:only-child::after, .tree-container li:only-child::before {
        display: none;
    }
    .tree-container li:only-child { padding-top: 0; }
    .tree-container li:first-child::before, .tree-container li:last-child::after {
        border: 0 none;
    }
    .tree-container li:last-child::before {
        border-right: 1px solid var(--md-sys-color-outline);
        border-radius: 0 5px 0 0;
    }
    .tree-container li:first-child::after {
        border-radius: 5px 0 0 0;
    }

    /* Vertical connector to parent */
    .tree-container ul ul::before {
        content: '';
        position: absolute; 
        top: 0; 
        left: 50%;
        border-left: 1px solid var(--md-sys-color-outline);
        width: 0; 
        height: 20px;
    }

    /* Node Styling */
    .tree-node {
        border: 1px solid var(--md-sys-color-outline);
        padding: 12px 16px;
        display: inline-block;
        border-radius: 12px;
        background: white;
        transition: all 0.3s;
        min-width: 140px;
        position: relative;
    }
    .tree-node:hover {
        background: var(--md-sys-color-primary-container);
        border-color: var(--md-sys-color-primary);
        transform: scale(1.05);
    }
    .tree-node.root {
        background: var(--md-sys-color-primary);
        color: white;
        border: none;
    }
    .tree-node .node-actions {
        display: flex;
        justify-content: center;
        gap: 4px;
        margin-top: 8px;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .tree-node:hover .node-actions {
        opacity: 1;
    }
    .tree-node code {
        display: block;
        font-size: 10px;
        margin-top: 4px;
        opacity: 0.7;
    }

    /* Helper for horizontal layout */
    .tree-wrapper {
        overflow-x: auto;
        padding-bottom: 40px;
    }
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }
</style>

<div class="card tree-wrapper">
    <div class="tree-container clearfix">
        <?php
        function renderTreeVisual($roles, $isRoot = false) {
            if (empty($roles)) return;
            
            echo '<ul>';
            foreach ($roles as $role) {
                echo '<li>';
                
                $nodeClass = $isRoot ? 'tree-node root' : 'tree-node';
                echo '<div class="' . $nodeClass . '">';
                echo '<div style="font-weight: 700; font-size: 14px;">' . htmlspecialchars($role['libelle']) . '</div>';
                echo '<code>' . htmlspecialchars($role['code']) . '</code>';
                
                echo '<div class="node-actions">';
                echo '<a href="/admin/roles/edit/' . $role['id'] . '" style="color: inherit;" title="' . __('edit') . '"><span class="material-symbols-outlined" style="font-size: 16px;">edit</span></a>';
                echo '<form action="/admin/roles/delete/' . (int) $role['id'] . '" method="POST" style="display:inline" onsubmit="return confirm(\'' . htmlspecialchars(__('confirm_delete_role'), ENT_QUOTES) . '\')">';
                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(\App\Middleware\CsrfMiddleware::generateToken(), ENT_QUOTES) . '">';
                echo '<button type="submit" class="btn btn-text btn-danger" title="' . htmlspecialchars(__('delete'), ENT_QUOTES) . '"><span class="material-symbols-outlined" style="font-size:16px">delete</span></button>';
                echo '</form>';
                echo '</div>';
                
                echo '</div>';

                if (!empty($role['children'])) {
                    renderTreeVisual($role['children']);
                }
                
                echo '</li>';
            }
            echo '</ul>';
        }

        if (empty($tree)) {
            echo '<div style="text-align: center; padding: 32px; color: var(--md-sys-color-outline);">' . __('no_role_defined') . '</div>';
        } else {
            renderTreeVisual($tree, true);
        }
        ?>
    </div>
</div>
