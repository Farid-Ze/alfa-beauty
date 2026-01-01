<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?php echo e(__('errors.page_not_found')); ?> | Alfa Beauty</title>
    <link rel="icon" type="image/webp" href="<?php echo e(asset('images/logo.webp')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #FAFAFA;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .error-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #E5E4E2;
        }

        .error-logo {
            font-family: 'Instrument Serif', Georgia, serif;
            font-size: 1.5rem;
            color: #0A0A0A;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-logo img {
            height: 32px;
            width: auto;
        }

        /* Main Content */
        .error-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .error-container {
            text-align: center;
            max-width: 480px;
        }

        .error-code {
            font-family: 'Instrument Serif', Georgia, serif;
            font-size: 8rem;
            font-weight: 400;
            color: #0A0A0A;
            line-height: 1;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .error-divider {
            width: 60px;
            height: 2px;
            background: #C9A962;
            margin: 1.5rem auto;
        }

        .error-title {
            font-family: 'Instrument Serif', Georgia, serif;
            font-size: 1.75rem;
            font-weight: 400;
            color: #0A0A0A;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #6B7280;
            font-size: 0.9375rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .error-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #0A0A0A;
            color: #FFFFFF;
        }

        .btn-primary:hover {
            background: #1a1a1a;
        }

        .btn-secondary {
            background: transparent;
            color: #0A0A0A;
            border: 1px solid #E5E4E2;
        }

        .btn-secondary:hover {
            border-color: #C9A962;
            color: #C9A962;
        }

        /* Footer */
        .error-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #E5E4E2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-links {
            display: flex;
            gap: 1.5rem;
        }

        .footer-links a {
            color: #6B7280;
            text-decoration: none;
            font-size: 0.8125rem;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: #C9A962;
        }

        .footer-copy {
            color: #9CA3AF;
            font-size: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.25rem;
            }

            .error-footer {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="error-header">
        <a href="<?php echo e(url('/')); ?>" class="error-logo">
            <img src="<?php echo e(asset('images/logo.webp')); ?>" alt="Alfa Beauty">
        </a>
    </header>

    <main class="error-main">
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-divider"></div>
            <h1 class="error-title"><?php echo e(__('errors.page_not_found')); ?></h1>
            <p class="error-message"><?php echo e(__('errors.page_not_found_desc')); ?></p>
            <div class="error-actions">
                <a href="<?php echo e(url('/')); ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <?php echo e(__('errors.back_home')); ?>

                </a>
                <a href="<?php echo e(route('products.index')); ?>" class="btn btn-secondary">
                    <?php echo e(__('errors.browse_products')); ?>

                </a>
            </div>
        </div>
    </main>

    <footer class="error-footer">
        <div class="footer-links">
            <a href="<?php echo e(route('products.index')); ?>"><?php echo e(__('nav.products')); ?></a>
            <a href="https://wa.me/<?php echo e(config('services.whatsapp.business_number')); ?>" target="_blank">WhatsApp</a>
        </div>
        <span class="footer-copy">© <?php echo e(date('Y')); ?> Alfa Beauty. All rights reserved.</span>
    </footer>
</body>
</html>
<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/errors/404.blade.php ENDPATH**/ ?>