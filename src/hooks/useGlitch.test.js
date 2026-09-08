import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderHook, act } from "@testing-library/react";

vi.mock("../utils.js", () => ({ scrollTerminal: vi.fn() }));
vi.mock("../random.js", () => ({ cosmeticRandom: vi.fn(() => 0) }));
vi.mock("../glitches.json", () => ({ default: ["GLITCH: signal desync"] }));

import useGlitch from "./useGlitch.js";

const MIN_DELAY = 90000;

function makeRefs({ intro = false, printing = false } = {}) {
  return { introPlayingRef: { current: intro }, printingRef: { current: printing } };
}

beforeEach(() => {
  vi.useFakeTimers();
});

afterEach(() => {
  vi.useRealTimers();
});

describe("useGlitch", () => {
  it("appends a glitch message after the delay when enabled (default)", async () => {
    const { introPlayingRef, printingRef } = makeRefs();
    const setTerminalLines = vi.fn();
    renderHook(() => useGlitch(introPlayingRef, printingRef, setTerminalLines));

    await act(async () => {
      await vi.advanceTimersByTimeAsync(MIN_DELAY);
    });

    expect(setTerminalLines).toHaveBeenCalledTimes(1);
  });

  it("never schedules a timer or appends anything when enabled=false", async () => {
    const { introPlayingRef, printingRef } = makeRefs();
    const setTerminalLines = vi.fn();
    renderHook(() => useGlitch(introPlayingRef, printingRef, setTerminalLines, false));

    await act(async () => {
      await vi.advanceTimersByTimeAsync(MIN_DELAY * 5);
    });

    expect(setTerminalLines).not.toHaveBeenCalled();
  });

  it("defers the glitch while the intro is playing or a command is printing", async () => {
    const { introPlayingRef, printingRef } = makeRefs({ intro: true });
    const setTerminalLines = vi.fn();
    renderHook(() => useGlitch(introPlayingRef, printingRef, setTerminalLines));

    await act(async () => {
      await vi.advanceTimersByTimeAsync(MIN_DELAY);
    });
    expect(setTerminalLines).not.toHaveBeenCalled();

    introPlayingRef.current = false;
    await act(async () => {
      await vi.advanceTimersByTimeAsync(MIN_DELAY);
    });
    expect(setTerminalLines).toHaveBeenCalledTimes(1);
  });

  it("clears the pending timer on unmount", async () => {
    const { introPlayingRef, printingRef } = makeRefs();
    const setTerminalLines = vi.fn();
    const { unmount } = renderHook(() => useGlitch(introPlayingRef, printingRef, setTerminalLines));

    unmount();

    await act(async () => {
      await vi.advanceTimersByTimeAsync(MIN_DELAY * 5);
    });
    expect(setTerminalLines).not.toHaveBeenCalled();
  });
});
