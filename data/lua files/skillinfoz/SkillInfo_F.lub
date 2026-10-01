-- SkillInfo_F.lua - Fully Safe & Error-Proof Version

function GetInheritJob(job)
    if JOB_INHERIT_LIST == nil then
        return nil
    end
    return JOB_INHERIT_LIST[job]
end

function CheckNeedSkillList(jobID)
    if All_NeedSkillList == nil then
        return false
    end
    if All_NeedSkillList[jobID] == nil then
        return false
    end
    return true
end

function SetNeedSkillList(SKID, depth, skillInfo)
    if All_NeedSkillList == nil then
        All_NeedSkillList = {}
    end
    if skillInfo == nil then
        return
    end
    if All_NeedSkillList[skillInfo] == nil then
        All_NeedSkillList[skillInfo] = {}
    end
    local list = All_NeedSkillList[skillInfo]
    list[#list + 1] = { SKID = SKID, depth = depth }
end

function GetSkillInfo(SKID)
    if SKILL_INFO_LIST == nil then
        return nil
    end
    return SKILL_INFO_LIST[SKID]
end

function AddNeedSkillList(SKID, depth, idx)
    if c_AddNeedSkillList then
        c_AddNeedSkillList(SKID, depth, idx)
    end
end

function InitSkillTreeView(jobID, arrayNum)
    if SKILL_TREEVIEW_FOR_JOB == nil or SKILL_TREEVIEW_FOR_JOB[jobID] == nil then
        return
    end
    local skillList = SKILL_TREEVIEW_FOR_JOB[jobID]
    for skillPos, skillID in pairs(skillList) do
        local skillInfo = GetSkillInfo(skillID)
        if skillInfo ~= nil then
            local strSkillID = skillInfo.strSkillID or ""
            local strSkillName = skillInfo.SkillName or ""
            local MaxLv = skillInfo.MaxLv or 1
            local UserUpgradable = 1
            if skillInfo.Type == "Quest" or skillInfo.Type == "Soul" then
                UserUpgradable = 0
            end
            if c_AddSkillList then
                c_AddSkillList(jobID, arrayNum, skillPos, skillID, strSkillID, strSkillName, MaxLv, UserUpgradable)
            end
        end
    end
end

function GetSkillIdName(SkillID)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SkillID] == nil then
        return ""
    end
    return SKILL_INFO_LIST[SkillID].strSkillID or ""
end

function GetSkillName(SkillID)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SkillID] == nil then
        return ""
    end
    return SKILL_INFO_LIST[SkillID].SkillName or ""
end

function IsLevelUseSkill(SkillID)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SkillID] == nil then
        return false
    end
    local obj = SKILL_INFO_LIST[SkillID]
    if obj.bSeperateLv ~= nil then
        return obj.bSeperateLv
    end
    if obj.SpAmount ~= nil and type(obj.SpAmount) == "table" and #obj.SpAmount > 1 then
        return true
    end
    return false
end

function GetLevelUseSpAmount(SkillID, idx)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SkillID] == nil then
        return 0
    end
    local obj = SKILL_INFO_LIST[SkillID]
    if obj.SpAmount == nil then
        return 0
    end
    if type(obj.SpAmount) == "table" then
        if obj.SpAmount[idx] ~= nil then
            return obj.SpAmount[idx]
        end
        return obj.SpAmount[#obj.SpAmount] or 0
    end
    return obj.SpAmount or 0
end

function GetSkillDescript(JobID, SKID, bChangeColor)
    if SKILL_DESCRIPT == nil or SKILL_DESCRIPT[SKID] == nil then
        return ""
    end
    local descript = SKILL_DESCRIPT[SKID]
    return descript
end

function GetSkillAttackRange(in_SKID, in_Level, in_curMaxLv)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[in_SKID] == nil then
        return 1
    end
    local obj = SKILL_INFO_LIST[in_SKID]
    if obj.AttackRange == nil then
        return 1
    end
    if type(obj.AttackRange) == "table" then
        if in_Level ~= nil and obj.AttackRange[in_Level] ~= nil then
            return obj.AttackRange[in_Level]
        end
        local maxLv = obj.MaxLv or 1
        if in_curMaxLv ~= nil and in_curMaxLv > 0 and in_curMaxLv < maxLv then
            maxLv = in_curMaxLv
        end
        if obj.AttackRange[maxLv] ~= nil then
            return obj.AttackRange[maxLv]
        end
        return obj.AttackRange[1] or 1
    end
    return obj.AttackRange or 1
end

function GetSkillScale(in_SKID, in_Level)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[in_SKID] == nil then
        return 0
    end
    local obj = SKILL_INFO_LIST[in_SKID]
    if obj.SkillScale == nil then
        return 0
    end
    if type(obj.SkillScale) == "table" then
        if in_Level ~= nil and obj.SkillScale[in_Level] ~= nil then
            return obj.SkillScale[in_Level]
        end
        return obj.SkillScale[1] or 0
    end
    return obj.SkillScale or 0
end

function ChangeSkillTabName(in_job, in_1sttab, in_2ndtab, in_3rdtab, in_4thtab)
    if JobSkillTab == nil then
        JobSkillTab = {}
    end
    JobSkillTab[in_job] = {
        TabName1st = in_1sttab or "1st",
        TabName2nd = in_2ndtab or "2nd",
        TabName3rd = in_3rdtab or "3rd",
        TabName4th = in_4thtab or "4th"
    }
end

function JobSkillTab_GetTabName(in_job)
    if JobSkillTab == nil or JobSkillTab[in_job] == nil then
        return "1st", "2nd", "3rd", "4th"
    end
    local t = JobSkillTab[in_job]
    return t.TabName1st or "1st", t.TabName2nd or "2nd", t.TabName3rd or "3rd", t.TabName4th or "4th"
end

function IsSkillUsesAp(SkillID)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SkillID] == nil then
        return false
    end
    local obj = SKILL_INFO_LIST[SkillID]
    return obj.ApAmount ~= nil
end

function GetLevelUseApAmount(SkillID, idx)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SkillID] == nil then
        return 0
    end
    local obj = SKILL_INFO_LIST[SkillID]
    if obj.ApAmount == nil then
        return 0
    end
    if type(obj.ApAmount) == "table" then
        if obj.ApAmount[idx] ~= nil then
            return obj.ApAmount[idx]
        end
        return obj.ApAmount[#obj.ApAmount] or 0
    end
    return obj.ApAmount or 0
end

function GetSkillMaxLv(SKID)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SKID] == nil then
        return 1
    end
    return SKILL_INFO_LIST[SKID].MaxLv or 1
end

function IsPassiveSkill(SKID)
    if SKILL_INFO_LIST == nil or SKILL_INFO_LIST[SKID] == nil then
        return false
    end
    local obj = SKILL_INFO_LIST[SKID]
    if obj.IsPassive ~= nil then
        return obj.IsPassive
    end
    if obj.Type == "Passive" then
        return true
    end
    return false
end
