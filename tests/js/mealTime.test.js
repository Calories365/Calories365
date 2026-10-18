import { describe, expect, it } from "vitest";
import { suggestedMeals } from "@/helpers/mealTime.js";

describe("suggestedMeals", () => {
    it("puts breakfast first in the morning", () => {
        expect(suggestedMeals(8)).toEqual(["morning", "dinner", "supper"]);
    });

    it("puts lunch first from 11 to 16", () => {
        expect(suggestedMeals(11)).toEqual(["dinner", "morning", "supper"]);
        expect(suggestedMeals(15)).toEqual(["dinner", "morning", "supper"]);
    });

    it("puts dinner first from 16", () => {
        expect(suggestedMeals(16)).toEqual(["supper", "morning", "dinner"]);
        expect(suggestedMeals(23)).toEqual(["supper", "morning", "dinner"]);
    });
});
