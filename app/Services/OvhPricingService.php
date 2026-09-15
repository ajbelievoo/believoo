<?php

namespace App\Services;

use App\Models\OvhPricingRule;

class OvhPricingService
{
    public function calculate(
        float $costInInr,
        string $category,
        string $planCode,
        float $defaultMarginPercent = 25.0
    ): array {
        $rule = $this->resolveRule($category, $planCode);

        if (!$rule) {
            $marginPercent = $defaultMarginPercent;
            $fixedMarkup = 0;
            $minMargin = null;
            $maxMargin = null;
        } else {
            $marginPercent = $rule->margin_percent ?? $defaultMarginPercent;
            $fixedMarkup = $rule->fixed_markup ?? 0;
            $minMargin = $rule->min_margin_percent;
            $maxMargin = $rule->max_margin_percent;
        }

        $sale = $costInInr * (1 + $marginPercent / 100) + $fixedMarkup;

        if ($minMargin !== null && $maxMargin !== null) {
            $effectiveMargin = $costInInr > 0 ? (($sale - $costInInr) / $costInInr) * 100 : 0;
            $effectiveMargin = max($minMargin, min($maxMargin, $effectiveMargin));
            $sale = $costInInr * (1 + $effectiveMargin / 100) + $fixedMarkup;
        } elseif ($minMargin !== null) {
            $minSale = $costInInr * (1 + $minMargin / 100);
            $sale = max($sale, $minSale);
        } elseif ($maxMargin !== null) {
            $maxSale = $costInInr * (1 + $maxMargin / 100);
            $sale = min($sale, $maxSale);
        }

        $roundTo = $rule ? ($rule->round_to ?? 2) : 2;
        $sale = $this->round($sale, $roundTo);

        $effectiveMargin = $costInInr > 0 ? (($sale - $costInInr) / $costInInr) * 100 : 0;

        return [
            'cost_price' => round($costInInr, 4),
            'sale_price' => $sale,
            'margin_percent' => round($effectiveMargin, 4),
            'rule_id' => $rule?->id,
        ];
    }

    public function resolveRule(string $category, string $planCode): ?OvhPricingRule
    {
        $category = strtolower($category);
        $planCode = strtolower($planCode);

        $rules = OvhPricingRule::active()->get();

        $best = null;
        $bestScore = 0;

        foreach ($rules as $rule) {
            $ruleCategory = strtolower($rule->category);
            $rulePlan = strtolower($rule->plan_code);

            $categoryMatch = $ruleCategory === 'all' || $ruleCategory === $category;
            $planMatch = $rulePlan === '*' || $rulePlan === $planCode;

            if ($categoryMatch && $planMatch) {
                $score = ($ruleCategory === $category ? 2 : 0)
                       + ($rulePlan === $planCode ? 2 : 0)
                       + ($rule->priority / 1000);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $rule;
                }
            }
        }

        return $best;
    }

    protected function round(float $value, int $roundTo): float
    {
        $multiplier = 10 ** $roundTo;
        return round($value * $multiplier) / $multiplier;
    }
}
