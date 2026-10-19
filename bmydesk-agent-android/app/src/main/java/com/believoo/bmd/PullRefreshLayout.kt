package com.believoo.bmd

import android.content.Context
import android.view.Gravity
import android.view.MotionEvent
import android.view.View
import android.view.ViewConfiguration
import android.widget.FrameLayout
import android.widget.ProgressBar

/**
 * Dependency-free pull-to-refresh (SwipeRefreshLayout behaviour without the
 * androidx artifact): drag down from the top of the content — while it can't
 * scroll up — past a threshold, release, and [onRefresh] fires with a spinner
 * pinned at the top until [stopRefresh] is called.
 */
class PullRefreshLayout(context: Context) : FrameLayout(context) {

    var onRefresh: (() -> Unit)? = null
    @Volatile var refreshing = false; private set

    private val slop = ViewConfiguration.get(context).scaledTouchSlop
    private val d = resources.displayMetrics.density
    private val trigger = (d * 68).toInt()
    // branded spinner — rose ring on a dark chip instead of the stock gray dot
    private val bar = ProgressBar(context).apply {
        visibility = GONE
        elevation = d * 6
        indeterminateTintList = android.content.res.ColorStateList.valueOf(
            android.graphics.Color.parseColor("#fb7185"))
        background = android.graphics.drawable.GradientDrawable().apply {
            shape = android.graphics.drawable.GradientDrawable.OVAL
            setColor(android.graphics.Color.parseColor("#e0101526"))
            setStroke((d * 1).toInt(), android.graphics.Color.parseColor("#2a3752"))
        }
        val p = (d * 7).toInt()
        setPadding(p, p, p, p)
    }
    private var downY = 0f
    private var pulling = false

    init {
        addView(bar, LayoutParams(LayoutParams.WRAP_CONTENT, LayoutParams.WRAP_CONTENT,
            Gravity.TOP or Gravity.CENTER_HORIZONTAL).apply { topMargin = (d * 10).toInt() })
    }

    /** The wrapped content view (the child that isn't the spinner). */
    private fun content(): View? {
        for (i in 0 until childCount) getChildAt(i).let { if (it !== bar) return it }
        return null
    }

    private fun canPull(): Boolean {
        val c = content() ?: return true
        return !c.canScrollVertically(-1)
    }

    override fun onInterceptTouchEvent(ev: MotionEvent): Boolean {
        if (refreshing) return false
        when (ev.actionMasked) {
            MotionEvent.ACTION_DOWN -> { downY = ev.y; pulling = false }
            MotionEvent.ACTION_MOVE -> if (canPull() && ev.y - downY > slop * 2) pulling = true
        }
        return pulling
    }

    override fun onTouchEvent(ev: MotionEvent): Boolean {
        when (ev.actionMasked) {
            MotionEvent.ACTION_MOVE -> {
                val drag = (ev.y - downY).coerceAtLeast(0f)
                content()?.translationY = (drag * 0.42f).coerceAtMost(trigger * 1.6f)
                bar.visibility = VISIBLE
                bar.alpha = (drag / trigger).coerceIn(0.25f, 1f)
                bar.translationY = (drag * 0.3f).coerceAtMost(trigger.toFloat())
                return true
            }
            MotionEvent.ACTION_UP, MotionEvent.ACTION_CANCEL -> {
                val drag = ev.y - downY
                pulling = false
                if (ev.actionMasked == MotionEvent.ACTION_UP && drag > trigger) {
                    refreshing = true
                    bar.alpha = 1f; bar.translationY = 0f
                    content()?.animate()?.translationY(d * 54)?.setDuration(160)?.start()
                    onRefresh?.invoke()
                } else reset()
                return true
            }
        }
        return pulling
    }

    private fun reset() {
        bar.visibility = GONE; bar.alpha = 1f; bar.translationY = 0f
        content()?.animate()?.translationY(0f)?.setDuration(160)?.start()
    }

    /** Programmatic refresh (crash-recovery rejoin, deep-link connect). */
    fun startRefresh() { refreshing = true; bar.visibility = VISIBLE; bar.alpha = 1f }

    fun stopRefresh() { refreshing = false; reset() }
}
