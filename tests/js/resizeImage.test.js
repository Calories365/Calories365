import { describe, expect, it } from "vitest";
import { fitWithin, MAX_SIDE } from "@/helpers/resizeImage.js";

describe("fitWithin", () => {
    it("scales a phone photo down to the longer side", () => {
        expect(fitWithin(4032, 3024)).toEqual({
            width: MAX_SIDE,
            height: 1200,
        });
        expect(fitWithin(3024, 4032)).toEqual({
            width: 1200,
            height: MAX_SIDE,
        });
    });

    it("leaves a small picture as it is", () => {
        expect(fitWithin(1280, 960)).toEqual({ width: 1280, height: 960 });
    });
});
