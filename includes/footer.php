<footer class="site-footer">
    <div class="container">
        <p class="text-center">&copy; 2025 Vav Champ</p>
    </div>
</footer>

<style>
    /* Estilos do Footer Universal - Largura Total */
    .site-footer {
        text-align: center;
        color: var(--gray-300);
        padding: 25px 0;
        margin-top: 40px;
        background: linear-gradient(120deg, var(--gray-800), var(--gray-900));
        position: relative;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        width: 100vw;
        left: 50%;
        transform: translateX(-50%);
        box-sizing: border-box;
    }

    .site-footer::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.1) 0%, transparent 40%);
        pointer-events: none;
    }

    .site-footer p {
        margin: 0;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }

    /* Reset para garantir que não haja margens indesejadas */
    body, html {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>