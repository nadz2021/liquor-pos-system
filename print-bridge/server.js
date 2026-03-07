import express from "express";
import cors from "cors";
import escpos from "escpos";
import escposUSB from "escpos-usb";

escpos.USB = escposUSB;

const app = express();
app.use(cors());
app.use(express.json({ limit: "1mb" }));

function getDevice() {
  // Picks the first detected USB printer.
  // If you have multiple printers later, we can add vendor/product selection.
  return new escpos.USB();
}

app.post("/print", (req, res) => {
  try {
    const { text, cut = true, drawer = false } = req.body || {};
    if (!text) return res.status(422).json({ ok: false, msg: "Missing text" });

    const device = getDevice();
    const printer = new escpos.Printer(device, { encoding: "GB18030" });

    device.open((err) => {
      if (err) return res.status(500).json({ ok: false, msg: String(err) });

      printer.text(text);

      if (drawer) {
        // Kick drawer (works only if you have a drawer connected to printer RJ11 and printer supports it)
        try { printer.cashdraw(2); } catch {}
      }

      if (cut) {
        try { printer.cut(); } catch {}
      }

      printer.close();
      return res.json({ ok: true });
    });
  } catch (e) {
    return res.status(500).json({ ok: false, msg: String(e) });
  }
});

app.listen(9123, "127.0.0.1", () => {
  console.log("Print bridge running on http://127.0.0.1:9123");
});
