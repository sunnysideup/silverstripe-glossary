document.addEventListener('DOMContentLoaded', () => {
    const scrollToAnchor = () => {
        if (!window.glossaryPageAnchorSetByPHP) return

        const el = document.getElementById(window.glossaryPageAnchorSetByPHP)
        if (!el) return

        const targetTop = el.offsetTop

        const scroll = (duration, cb) => {
            const start =
                document.documentElement.scrollTop || document.body.scrollTop
            const delta = targetTop - start
            let startTime = null

            const animate = ts => {
                if (!startTime) startTime = ts
                const progress = ts - startTime
                const pct = Math.min(progress / duration, 1)

                document.documentElement.scrollTop = document.body.scrollTop =
                    start + delta * pct

                if (pct < 1) {
                    requestAnimationFrame(animate)
                } else if (typeof cb === 'function') {
                    cb()
                }
            }

            requestAnimationFrame(animate)
        }

        scroll(700, () => {
            el.classList.add('highlight')
            if (typeof window.glossaryPageAnchorCallback === 'function') {
                window.glossaryPageAnchorCallback()
            }
        })
    }

    const showHideLetters = () => {
        const index = document.querySelector('.index-for-glossary')
        const sections = document.querySelectorAll('.glossary-separator')

        if (!index) return

        let activeId = null

        const showOnly = id => {
            sections.forEach(sec => {
                sec.style.display = sec.id === id ? 'block' : 'none'
            })
            activeId = id
        }

        const showAll = () => {
            sections.forEach(sec => {
                sec.style.display = 'block'
            })
            activeId = null
        }

        index.addEventListener('click', e => {
            const el = e.target
            if (el.tagName !== 'A') return
            e.preventDefault()

            const id = el.getAttribute('href').split('#')[1]
            if (!id) return

            // Toggle off if clicking the same letter
            if (activeId === id) {
                el.classList.remove('current')
                showAll()
                return
            }
            el.classList.add('current')

            // Activate new letter filter
            showOnly(id)

            const section = document.getElementById(id)
            if (section) section.scrollIntoView({ behavior: 'smooth' })
        })
    }

    scrollToAnchor()
    showHideLetters()
})
