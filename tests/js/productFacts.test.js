import { describe, expect, it } from "vitest";
import { factsChanged, factsOf } from "@/helpers/productFacts.js";

const found = { calories: 105, protein: 3, fats: 1, carbs: 20, weight: 150 };

describe("factsChanged", () => {
    it("leaves a product the same when only the weight eaten changes", () => {
        expect(factsChanged({ ...found, weight: 250 }, factsOf(found))).toBe(
            false
        );
    });

    it("sees a change of calories, protein, fats or carbs", () => {
        for (const field of ["calories", "protein", "fats", "carbs"]) {
            expect(
                factsChanged({ ...found, [field]: 999 }, factsOf(found))
            ).toBe(true);
        }
    });

    it("takes a number typed into a field for the same number", () => {
        expect(
            factsChanged({ ...found, calories: "105" }, factsOf(found))
        ).toBe(false);
    });

    it("sees no change without the facts the product came with", () => {
        expect(factsChanged({ ...found, calories: 300 }, undefined)).toBe(
            false
        );
    });
});
