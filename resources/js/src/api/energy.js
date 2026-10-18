import axios from "@/api/axios";

// The real daily expenditure the diary measured, see EnergyBalance.

export const getEnergy = () => axios.get("/api/energy");

export const applyEnergyNorm = () => axios.post("/api/energy/apply");
